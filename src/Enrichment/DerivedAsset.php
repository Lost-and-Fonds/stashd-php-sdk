<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Enrichment;

use Stashd\PluginSdk\Helper\Artifact;

/**
 * A new saved file derived from one or more existing files.
 */
final readonly class DerivedAsset
{
    /**
     * Describe the new file and its source files.
     * @param list<string> $derivedFrom IDs of saved files used to create this one.
     */
    public function __construct(
        /**
         * Finished file created by this enrichment call.
         */
        public Artifact $artifact,
        /**
         * @var list<string> IDs of saved files used to create this one.
         */
        public array $derivedFrom,
        /**
         * Plugin-defined activity name.
         */
        public string $activity,
        /**
         * Version of the activity that created the file.
         */
        public string $activityVersion,
    ) {}
}
