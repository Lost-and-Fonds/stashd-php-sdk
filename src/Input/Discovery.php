<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Input;

use RuntimeException;
use Stashd\PluginSdk\Helper\Credential;
use Stashd\PluginSdk\Helpers;
use Stashd\PluginSdk\Runtime\Codec\AuthorValues;
use Stashd\PluginSdk\Runtime\Codec\Values;
use Stashd\PluginSdk\Runtime\Invocation;
use Stashd\PluginSdk\Runtime\ProtocolViolation;
use stdClass;

/**
 * Save discovered items in batches, along with a restart point or final result.
 */
final class Discovery
{
    /**
     * Whether the host has saved the final batch.
     */
    private bool $finished = false;

    /**
     * Create a discovery run with its settings and restart state.
     *
     * @param Invocation $invocation Connection used to save batches during this call.
     * @param string $inputId Stable input ID returned when the source was resolved.
     * @param string $intent Whether to refresh known work or find all items.
     * @param Source $options Caller-selected settings for this run.
     * @param string|null $continuation Restart data for unfinished work; null starts a new run.
     * @param string|null $refreshState Baseline from a completed run; null when none is available.
     * @param int $maximumItemsPerBatch Positive upper limit on items in one batch.
     * @param Helpers $helpers Tools and credentials available during this call.
     */
    public function __construct(
        private readonly Invocation $invocation,
        public readonly string $inputId,
        public readonly string $intent,
        public readonly Source $options,
        public readonly ?string $continuation,
        public readonly ?string $refreshState,
        public readonly int $maximumItemsPerBatch,
        private readonly Helpers $helpers,
    ) {
        if ($maximumItemsPerBatch < 1) {
            throw new ProtocolViolation('Discovery batch maximum must be positive');
        }
    }

    /**
     * Return credential selectors available for this run.
     * @return list<Credential>
     */
    public function credentials(): array
    {
        return $this->helpers->credentials();
    }

    /**
     * Whether the final batch was acknowledged by the host.
     */
    public function finished(): bool
    {
        return $this->finished;
    }

    /**
     * Save items and their restart point or final result together.
     * @param list<DiscoveredItem> $items
     */
    public function commit(array $items, ?string $continuation = null, ?DiscoveryFinish $finish = null): void
    {
        if ($this->finished || ($continuation === null) === ($finish === null) || count($items) > $this->maximumItemsPerBatch) {
            throw new ProtocolViolation('Discovery batch must be bounded and have exactly one progress outcome');
        }

        $wireItems = [];

        foreach ($items as $item) {
            $wireItems[] = (object) [
                'id' => Values::text($item->id), 'reference' => Values::text($item->reference),
                'delegation' => $item->delegation === null ? null : (object) ['reference' => Values::text($item->delegation)],
                'size-bytes' => AuthorValues::size($item->sizeBytes), 'size-estimated' => $item->sizeEstimated,
                'metadata' => array_map(AuthorValues::encodeMetadata(...), $item->metadata),
            ];
        }

        $progress = $finish === null ? (object) ['tag' => 'more', 'value' => (object) ['value' => Values::text($continuation)]] : (object) [
            'tag' => 'finished', 'value' => match ($finish->kind) {
                'exhaustive' => (object) ['tag' => 'exhaustive', 'value' => $finish->refreshState === null ? null : (object) ['value' => Values::text($finish->refreshState)]],
                'partial' => (object) ['tag' => 'partial', 'value' => array_map(self::deficiency(...), $finish->deficiencies)],
                'indeterminate' => (object) ['tag' => 'indeterminate', 'value' => self::diagnostic($finish->diagnostic ?? throw new ProtocolViolation('Missing discovery diagnostic'))],
                default => throw new ProtocolViolation('Unknown discovery finish outcome'),
            },
        ];
        $result = $this->invocation->call('stashd:plugin/input-host.commit-discovery-batch', (object) ['batch' => (object) [
            'items' => $wireItems, 'progress' => $progress,
        ]]);

        if (!$result instanceof stdClass) {
            $this->invocation->violate('Discovery commit requires a result');
        }

        $result = Values::record($result, property_exists($result, 'error') ? ['error'] : ['ok']);

        if (property_exists($result, 'error')) {
            if ($result->error !== 'rejected') {
                $this->invocation->violate('Unknown discovery commit error');
            }

            throw new RuntimeException('Host rejected discovery batch');
        }

        if ($result->ok !== null) {
            $this->invocation->violate('Discovery commit must return unit');
        }

        if ($finish !== null) {
            $this->finished = true;
        }
    }

    /**
     * Convert an incomplete-coverage reason to its public result shape.
     */
    public static function deficiency(Deficiency $deficiency): stdClass
    {
        return (object) ['disposition' => $deficiency->disposition, 'diagnostic' => self::diagnostic($deficiency->diagnostic)];
    }

    /**
     * Convert the explanation and supporting plugin-owned evidence.
     */
    public static function diagnostic(Diagnostic $diagnostic): stdClass
    {
        return (object) ['message' => Values::text($diagnostic->message), 'evidence' => array_map(AuthorValues::encodeMetadata(...), $diagnostic->evidence)];
    }
}
