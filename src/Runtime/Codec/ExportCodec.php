<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime\Codec;

use Stashd\PluginSdk\CollectionExport\Collection;
use Stashd\PluginSdk\CollectionExport\Entry;
use Stashd\PluginSdk\CollectionExport\Exporter;
use Stashd\PluginSdk\CollectionExport\Failure;
use Stashd\PluginSdk\CollectionExport\Setting;
use Stashd\PluginSdk\Runtime\ProtocolViolation;
use stdClass;

/**
 * Focused Collection Export mapping, keeping JSON entirely outside the author interface.
 */
final class ExportCodec
{
    /**
     * Decode exact WIT arguments, invoke a typed exporter and encode its canonical result.
     */
    public function invoke(Exporter $exporter, stdClass $params): stdClass
    {
        Envelope::exact($params, ['exporter', 'collection', 'options']);
        $identity = $this->text($params->exporter);
        $collection = $this->record($params->collection, ['title', 'entries']);
        $entries = [];

        foreach ($this->items($collection->entries) as $raw) {
            $entry = $this->record($raw, ['reference', 'title']);
            $entries[] = new Entry($this->text($entry->reference), $this->optionalText($entry->title));
        }

        $settings = [];

        foreach ($this->items($params->options) as $raw) {
            $setting = $this->record($raw, ['key', 'value']);
            $variant = $this->record($setting->value, ['tag', 'value']);
            $tag = $this->text($variant->tag);
            $value = $variant->value;
            Scalar::validate(match ($tag) {
                'boolean' => 'bool', 'number' => 's64', 'text' => 'string',
                default => throw new ProtocolViolation('Unknown Collection Export option case'),
            }, $value);

            if ($tag === 'number' && is_string($value)) {
                $value = (int) $value;
            }

            if (!is_bool($value) && !is_int($value) && !is_string($value)) {
                throw new ProtocolViolation('Invalid exporter option payload');
            }

            $settings[] = new Setting($this->text($setting->key), $value);
        }

        $result = $exporter->export($identity, new Collection($this->optionalText($collection->title), ...$entries), ...$settings);

        if ($result instanceof Failure) {
            return (object) ['error' => (object) ['tag' => $result->kind->value, 'value' => (object) [
                'message' => $this->text($result->message), 'retryable' => $result->retryable,
            ]]];
        }

        $bytes = [];

        for ($offset = 0; $offset < strlen($result->contents); ++$offset) {
            $bytes[] = ord($result->contents[$offset]);
        }

        return (object) ['ok' => (object) ['filename' => $this->text($result->filename),
            'media-type' => $this->text($result->mediaType), 'contents' => $bytes]];
    }

    /**
     * Require exact record members without accepting list-shaped PHP values.
     * @param list<string> $fields
     */
    private function record(mixed $value, array $fields): stdClass
    {
        if (!$value instanceof stdClass) {
            throw new ProtocolViolation('Expected WIT record');
        }

        Envelope::exact($value, $fields);

        return $value;
    }

    /**
     * Validate ordered list structure, preserving duplicates for plugin-owned interpretation.
     * @return list<mixed>
     */
    private function items(mixed $value): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            throw new ProtocolViolation('Expected WIT list');
        }

        return $value;
    }

    /**
     * Validate a Unicode scalar string without normalization.
     */
    private function text(mixed $value): string
    {
        if (!is_string($value) || preg_match('//u', $value) !== 1) {
            throw new ProtocolViolation('Expected Unicode string');
        }

        return $value;
    }

    /**
     * Preserve absence independently of empty strings.
     */
    private function optionalText(mixed $value): ?string
    {
        return $value === null ? null : $this->text($value);
    }
}
