<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

/**
 * Cancellation won the first-terminal-condition race; no writer returns.
 */
final readonly class HelperTerminalCancelled implements HelperTerminal {}
