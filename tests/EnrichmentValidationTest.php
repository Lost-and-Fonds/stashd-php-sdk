<?php

declare(strict_types=1);

use Stashd\PluginSdk\Contract\EnrichmentPlugin\Capability;
use Stashd\PluginSdk\Contract\EnrichmentPlugin\ConfigurationChoice;
use Stashd\PluginSdk\Contract\EnrichmentPlugin\ConfigurationOption;
use Stashd\PluginSdk\Contract\EnrichmentPlugin\ConfigurationValue;
use Stashd\PluginSdk\Contract\EnrichmentPlugin\PluginErrorInvalidConfiguration;
use Stashd\PluginSdk\Contract\EnrichmentPlugin\PluginErrorUnsupported;
use Stashd\PluginSdk\Runtime\EnrichmentValidation;
use Stashd\PluginSdk\Runtime\ProtocolViolation;

it('validates capability identity before caller configuration', function (): void {
    $capability = new Capability('opaque', 'r1', [new ConfigurationOption('key', 'Label', true, [new ConfigurationChoice('value', 'Value')])]);
    expect(EnrichmentValidation::select([$capability], 'opaque', 'old', []))->toBeInstanceOf(PluginErrorUnsupported::class)
        ->and(EnrichmentValidation::select([$capability], 'opaque', 'r1', []))->toBeInstanceOf(PluginErrorInvalidConfiguration::class)
        ->and(EnrichmentValidation::select([$capability], 'opaque', 'r1', [new ConfigurationValue('key', 'value')]))->toBe($capability);
});

it('rejects duplicate caller keys rather than choosing a winner', function (): void {
    $capability = new Capability('', '', [new ConfigurationOption('', '', false, [new ConfigurationChoice('', '')])]);
    expect(EnrichmentValidation::select([$capability], '', '', [new ConfigurationValue('', ''), new ConfigurationValue('', '')]))->toBeInstanceOf(PluginErrorInvalidConfiguration::class)
        ->and(EnrichmentValidation::select([$capability], '', '', []))->toBe($capability);
});

it('rejects invalid producer descriptors as protocol violations', function (): void {
    $capability = new Capability('a', 'v', []);
    expect(fn() => EnrichmentValidation::descriptors([$capability, $capability]))->toThrow(ProtocolViolation::class);
    $empty = new Capability('a', 'v', [new ConfigurationOption('key', '', false, [])]);
    expect(fn() => EnrichmentValidation::descriptors([$empty]))->toThrow(ProtocolViolation::class);
    $choice = new ConfigurationChoice('same', '');
    $duplicates = new Capability('a', 'v', [new ConfigurationOption('key', '', false, [$choice, $choice])]);
    expect(fn() => EnrichmentValidation::descriptors([$duplicates]))->toThrow(ProtocolViolation::class);
    EnrichmentValidation::descriptors([]);
});
