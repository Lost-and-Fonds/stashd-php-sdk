<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

return new Config()
    ->setRules([
        '@PER-CS3x0' => true,

        'declare_strict_types' => true,

        'blank_line_before_statement' => [
            'statements' => [
                'break',
                'continue',
                'do',
                'for',
                'foreach',
                'if',
                'return',
                'throw',
                'try',
                'switch',
                'while'
            ]
        ],

        // Declaration PHPDoc is always multiline.
        'phpdoc_line_span' => [
            'case' => 'multi',
            'class' => 'multi',
            'const' => 'multi',
            'function' => 'multi',
            'method' => 'multi',
            'property' => 'multi',
        ],

        // Never leave class/attribute references fully qualified inline.
        'fully_qualified_strict_types' => [
            'import_symbols' => true,
        ],

        // Import global classes too, e.g. \NoDiscard -> use NoDiscard.
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => null,
            'import_functions' => null,
        ],

        // Keep the resulting import block tidy.
        'no_unused_imports' => true,
        'ordered_imports' => true,
        'single_import_per_statement' => true,
        'single_line_after_imports' => true,
    ])
    ->setFinder(Finder::create()->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/examples',
    ]));
