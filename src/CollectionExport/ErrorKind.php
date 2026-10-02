<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\CollectionExport;

/**
 * Plugin-authored export failure categories; host-reserved limit-exceeded is intentionally unavailable.
 */
enum ErrorKind: string
{
    /**
     * The exporter cannot handle this request.
     */
    case Unsupported = 'unsupported';
    /**
     * A requested collection entry was not found.
     */
    case NotFound = 'not-found';
    /**
     * Authentication prevented this export.
     */
    case Authentication = 'authentication';
    /**
     * The upstream service limited the request rate.
     */
    case RateLimited = 'rate-limited';
    /**
     * A required service is currently unavailable.
     */
    case Unavailable = 'unavailable';
    /**
     * The supplied collection data cannot be exported.
     */
    case InvalidData = 'invalid-data';
    /**
     * The export failed for another reason.
     */
    case Failed = 'failed';
}
