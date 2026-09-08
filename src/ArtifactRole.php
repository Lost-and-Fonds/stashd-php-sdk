<?php

declare(strict_types=1);

namespace Stashd\PluginSdk;

enum ArtifactRole: string
{
    case Primary = 'primary';
    case Captions = 'captions';
    case Artwork = 'artwork';
    case Metadata = 'metadata';
}
