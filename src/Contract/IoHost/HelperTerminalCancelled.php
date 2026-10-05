<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

/**
 * The process was cancelled before another outcome; no writer is returned.
 */
final readonly class HelperTerminalCancelled implements HelperTerminal {}
