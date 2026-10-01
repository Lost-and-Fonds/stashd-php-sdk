<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime;

use RuntimeException;

/**
 * Irrecoverable contract failure; callers must invalidate the channel, not return a lifecycle error.
 */
final class ProtocolViolation extends RuntimeException {}
