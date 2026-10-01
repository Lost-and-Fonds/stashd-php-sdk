<?php

declare(strict_types=1);

use Stashd\PluginSdk\Tooling\DocumentationChecker;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);
$checker = new DocumentationChecker();
$failures = [];
$directories = new RecursiveCallbackFilterIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    static fn (SplFileInfo $entry): bool => !in_array($entry->getFilename(), ['vendor', '.git', '.phpunit.cache'], true),
);

foreach (new RecursiveIteratorIterator($directories) as $file) {
    if (!$file instanceof SplFileInfo || $file->getExtension() !== 'php') {
        continue;
    }

    $source = file_get_contents($file->getPathname());

    if ($source === false) {
        throw new RuntimeException('Cannot read PHP source for documentation verification');
    }

    array_push($failures, ...$checker->check($source, substr($file->getPathname(), strlen($root) + 1)));
}

foreach ($failures as $failure) {
    fwrite(STDERR, $failure . PHP_EOL);
}

exit($failures === [] ? 0 : 1);
