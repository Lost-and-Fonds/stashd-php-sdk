<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Input;

use InvalidArgumentException;
use Stashd\PluginSdk\Helper\Artifact;

/**
 * Finished files and the coverage outcome for one acquired item.
 */
final readonly class AcquisitionResult
{
    /**
     * Keep finished files together with their coverage outcome.
     * @param list<Artifact> $artifacts Files saved for this item.
     * @param bool $complete Whether the intended acquisition scope was covered.
     * @param list<Deficiency> $deficiencies Reasons the item remains incomplete.
     */
    private function __construct(public array $artifacts, public bool $complete, public array $deficiencies = []) {}

    /**
     * Return all finished files for a completely acquired item.
     */
    public static function complete(Artifact ...$artifacts): self
    {
        return new self(array_values($artifacts), true);
    }

    /**
     * Return saved files with at least one reason acquisition is incomplete.
     * @param list<Artifact> $artifacts
     * @param list<Deficiency> $deficiencies
     */
    public static function partial(array $artifacts, array $deficiencies): self
    {
        if ($deficiencies === []) {
            throw new InvalidArgumentException('Partial acquisition needs at least one deficiency');
        }

        return new self($artifacts, false, $deficiencies);
    }
}
