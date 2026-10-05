<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Tooling;

use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;

/**
 * Enforces description-bearing PHPDoc on declarations, including promoted properties.
 */
final class DocumentationChecker
{
    /**
     * Parse source without executing it and return declaration locations lacking prose.
     *
     * @return list<string>
     */
    public function check(string $source, string $filename): array
    {
        $nodes = (new ParserFactory())->createForHostVersion()->parse($source) ?? [];
        $declarations = (new NodeFinder())->find($nodes, static fn(Node $node): bool
            => $node instanceof Node\Stmt\ClassLike
            || $node instanceof Node\Stmt\Function_
            || $node instanceof Node\Stmt\ClassMethod
            || $node instanceof Node\Stmt\Property
            || $node instanceof Node\Stmt\ClassConst
            || $node instanceof Node\Stmt\EnumCase);
        $failures = [];

        foreach ($declarations as $node) {
            $comment = $node->getDocComment();

            if ($comment === null || !$this->hasDescription($comment->getText())) {
                $failures[] = $filename . ':' . $node->getStartLine() . ': ' . $node->getType() . ' requires PHPDoc description prose';
            }
        }

        foreach ($declarations as $method) {
            if (!$method instanceof Node\Stmt\ClassMethod || strtolower($method->name->toString()) !== '__construct') {
                continue;
            }

            $doc = $method->getDocComment()?->getText() ?? '';

            foreach ($method->params as $parameter) {
                if (!$parameter->isPromoted()) {
                    continue;
                }

                $variable = $parameter->var;

                if (!$variable instanceof Node\Expr\Variable || !is_string($variable->name)) {
                    continue;
                }

                $documented = false;

                foreach (explode("\n", $doc) as $line) {
                    if (preg_match('/^[ \t]*\*[ \t]+@param[ \t]+.+?[ \t]+\$' . preg_quote($variable->name, '/') . '[ \t]+(.+)$/', $line, $matches) !== 1) {
                        continue;
                    }

                    $description = trim($matches[1]);
                    $plain = strtolower(trim($description, " \t.*-/"));
                    $documented = preg_match('/[\p{L}]/u', $description) === 1
                        && !str_starts_with($description, '@')
                        && !in_array($plain, ['', 'todo', 'tbd', 'n/a', 'description', 'string', 'int', 'bool', 'float', 'array', 'mixed', strtolower($variable->name)], true);

                    break;
                }

                if (!$this->hasDescription($parameter->getDocComment()?->getText() ?? '') && !$documented) {
                    $failures[] = $filename . ':' . $parameter->getStartLine() . ': Param requires PHPDoc description prose';
                }
            }
        }

        return $failures;
    }

    /**
     * Only text before the first annotation constitutes description, not multiline tag payloads.
     */
    private function hasDescription(string $comment): bool
    {
        $body = substr($comment, 3, -2);

        foreach (explode("\n", $body) as $line) {
            $text = trim(ltrim(trim($line), '*'));

            if (str_starts_with($text, '@')) {
                return false;
            }

            if ($text !== '') {
                return true;
            }
        }

        return false;
    }
}
