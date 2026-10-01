<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\CollectionExport;

/**
 * Plugin-authored export failure categories; host-reserved limit-exceeded is intentionally unavailable.
 */
enum ErrorKind: string
{
    case Unsupported = 'unsupported';
    case NotFound = 'not-found';
    case Authentication = 'authentication';
    case RateLimited = 'rate-limited';
    case Unavailable = 'unavailable';
    case InvalidData = 'invalid-data';
    case Failed = 'failed';
}
