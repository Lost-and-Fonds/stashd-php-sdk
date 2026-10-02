<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

/**
 * Host timeout ended the process; its staged writer is discarded.
 */
final readonly class HelperTerminalTimedOut implements HelperTerminal {}
