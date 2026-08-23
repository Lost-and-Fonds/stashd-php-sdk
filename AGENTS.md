# Plugin SDK repository instructions

This repository owns the PHP 8.5 authoring SDK and its examples. It depends on
`stashd/plugin-api`, but must not import Stashd core or contain provider
semantics. Use PHP 8.5, PER-CS3, strict PSR-4, and code-as-paragraphs vertical
spacing. Run `composer lint`, `composer test`, and `composer test:static`.
