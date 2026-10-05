<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime\Codec;

use Stashd\PluginSdk\Broadcast\ActionResult;
use Stashd\PluginSdk\Broadcast\Choice;
use Stashd\PluginSdk\Broadcast\Item;
use Stashd\PluginSdk\Broadcast\Publication;
use Stashd\PluginSdk\Broadcast\PublishedFile;
use Stashd\PluginSdk\Broadcast\Setting;
use Stashd\PluginSdk\Contract\BroadcastPlugin\OptionValueBoolean;
use Stashd\PluginSdk\Contract\BroadcastPlugin\OptionValueNumber;
use Stashd\PluginSdk\Contract\BroadcastPlugin\OptionValueText;
use Stashd\PluginSdk\Contract\BroadcastPlugin\Setting as ContractSetting;
use Stashd\PluginSdk\Runtime\Codec\Generated\BroadcastHostCodec;
use Stashd\PluginSdk\Runtime\Codec\Generated\BroadcastPluginCodec;
use Stashd\PluginSdk\Runtime\ProtocolViolation;
use stdClass;

/**
 * Converts public Broadcast values at lifecycle boundaries.
 */
final class BroadcastAuthorCodec
{
    /**
     * Map ordered setting values without changing keys.
     * @param list<ContractSetting> $values
     * @return list<Setting>
     */
    public static function settings(array $values): array
    {
        return array_map(static function (ContractSetting $setting): Setting {
            $value = match (true) {
                $setting->value instanceof OptionValueBoolean, $setting->value instanceof OptionValueNumber, $setting->value instanceof OptionValueText => $setting->value->value,
                default => throw new ProtocolViolation('Unknown Broadcast setting type'),
            };

            return new Setting($setting->key, $value);
        }, $values);
    }

    /**
     * Convert public settings to exact protocol settings.
     * @param list<Setting> $values
     * @return list<ContractSetting>
     */
    public static function contractSettings(array $values): array
    {
        return array_map(static function (Setting $setting): ContractSetting {
            $value = match (true) {
                is_bool($setting->value) => new OptionValueBoolean($setting->value),
                is_int($setting->value) => new OptionValueNumber($setting->value),
                default => new OptionValueText($setting->value),
            };

            return new ContractSetting($setting->key, $value);
        }, $values);
    }

    /**
     * Encode action results as choices and settings.
     */
    public static function actionResult(ActionResult $response): stdClass
    {
        return (object) ['ok' => (object) [
            'choices' => array_map(static fn(Choice $choice): stdClass => (object) ['value' => $choice->value, 'label' => $choice->label], $response->choices),
            'values' => array_map(BroadcastPluginCodec::encodeSetting(...), self::contractSettings($response->values)),
        ]];
    }

    /**
     * Encode publication status and optional local output.
     */
    public static function publication(Publication $publication): stdClass
    {
        return (object) ['ok' => (object) [
            'artifact' => $publication->artifact === null ? null : AuthorValues::artifact($publication->artifact),
            'files' => $publication->filesComplete ? 'complete' : 'not-applicable',
        ]];
    }

    /**
     * Decode one selected saved item and its granted files.
     */
    public static function item(mixed $wire): Item
    {
        $item = BroadcastHostCodec::decodeItem($wire);

        return new Item($item->id, array_map(AuthorValues::asset(...), $item->assets), array_map(AuthorValues::metadata(...), $item->metadata));
    }

    /**
     * Convert one file report to the generated exact contract type.
     */
    public static function file(PublishedFile $file): stdClass
    {
        return BroadcastHostCodec::encodePublishedFile(new \Stashd\PluginSdk\Contract\BroadcastHost\PublishedFile($file->itemId, $file->assetId, $file->relativePath));
    }
}
