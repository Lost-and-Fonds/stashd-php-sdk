<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Input;

/**
 * Reasons an Input operation can fail normally.
 */
enum ErrorKind: string
{
    /**
     * The requested operation or source is not supported.
     */
    case Unsupported = 'unsupported';
    /**
     * The source no longer exists.
     */
    case NotFound = 'not-found';
    /**
     * Credential access was denied.
     */
    case CredentialDenied = 'credential-denied';
    /**
     * The selected credential is unavailable.
     */
    case CredentialUnavailable = 'credential-unavailable';
    /**
     * The source rejected authentication.
     */
    case Authentication = 'authentication';
    /**
     * The source imposed a request limit.
     */
    case RateLimited = 'rate-limited';
    /**
     * The source is temporarily unavailable.
     */
    case Unavailable = 'unavailable';
    /**
     * The supplied data cannot be used.
     */
    case InvalidData = 'invalid-data';
    /**
     * The operation failed for another reason.
     */
    case Failed = 'failed';
}
