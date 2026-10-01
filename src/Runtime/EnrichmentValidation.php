<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime;

use Stashd\PluginSdk\Contract\EnrichmentPlugin\Capability;
use Stashd\PluginSdk\Contract\EnrichmentPlugin\ConfigurationValue;
use Stashd\PluginSdk\Contract\EnrichmentPlugin\PluginErrorInvalidConfiguration;
use Stashd\PluginSdk\Contract\EnrichmentPlugin\PluginErrorUnsupported;
use Stashd\PluginSdk\Contract\PluginTypes\PluginErrorDetail;

/**
 * Separates invalid producer descriptors from ordinary caller configuration failures.
 */
final class EnrichmentValidation
{
    /**
     * Validate exact identity uniqueness without interpreting labels or opaque revision syntax.
     * @param list<Capability> $capabilities
     */
    public static function descriptors(array $capabilities): void
    {
        $ids = [];

        foreach ($capabilities as $capability) {
            if (in_array($capability->id, $ids, true)) {
                throw new ProtocolViolation('Duplicate enrichment capability ID');
            }

            $ids[] = $capability->id;
            $keys = [];

            foreach ($capability->options as $option) {
                if (in_array($option->key, $keys, true) || $option->choices === []) {
                    throw new ProtocolViolation('Duplicate option key or empty enrichment choices');
                }

                $keys[] = $option->key;
                $values = [];

                foreach ($option->choices as $choice) {
                    if (in_array($choice->value, $values, true)) {
                        throw new ProtocolViolation('Duplicate enrichment choice value');
                    }

                    $values[] = $choice->value;
                }
            }
        }
    }

    /**
     * Resolve identity and revision before evaluating every caller selection without deduplication.
     * @param list<Capability> $capabilities
     * @param list<ConfigurationValue> $configuration
     */
    public static function select(array $capabilities, string $id, string $revision, array $configuration): Capability|PluginErrorUnsupported|PluginErrorInvalidConfiguration
    {
        self::descriptors($capabilities);
        $selected = null;

        foreach ($capabilities as $capability) {
            if ($capability->id === $id && $capability->revision === $revision) {
                $selected = $capability;
            }
        }

        if ($selected === null) {
            return new PluginErrorUnsupported(new PluginErrorDetail('Unknown, inapplicable or stale capability', false));
        }

        $seen = [];

        foreach ($configuration as $selection) {
            if (in_array($selection->key, $seen, true)) {
                return self::invalid();
            }

            $seen[] = $selection->key;
            $accepted = false;

            foreach ($selected->options as $option) {
                if ($option->key !== $selection->key) {
                    continue;
                }

                foreach ($option->choices as $choice) {
                    if ($choice->value === $selection->value) {
                        $accepted = true;
                    }
                }
            }

            if (!$accepted) {
                return self::invalid();
            }
        }

        foreach ($selected->options as $option) {
            if ($option->required && !in_array($option->key, $seen, true)) {
                return self::invalid();
            }
        }

        return $selected;
    }

    /**
     * Return a safe caller error without echoing potentially sensitive supplied values.
     */
    private static function invalid(): PluginErrorInvalidConfiguration
    {
        return new PluginErrorInvalidConfiguration(new PluginErrorDetail('Configuration must select valid choices once per declared key', false));
    }
}
