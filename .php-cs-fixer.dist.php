<?php

declare(strict_types=1);

use HipsterJazzbo\PhpStyle\PhpStyle;
use PhpCsFixer\Finder;

return PhpStyle::config(Finder::create()->in([
    __DIR__ . '/src',
    __DIR__ . '/tests',
    __DIR__ . '/examples',
]));
