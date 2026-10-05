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

        foreach ((new NodeFinder())->findInstanceOf($nodes, Node\Param::class) as $parameter) {
            if ($parameter->flags === 0) {
                continue;
            }

            $parameterName = $parameter->var;
            $parameterComment = $parameter->getDocComment()?->getText() ?? '';

            if (!$parameterName instanceof Node\Expr\Variable || !is_string($parameterName->name)) {
                continue;
            }

            $method = (new NodeFinder())->findFirst($nodes, static fn(Node $node): bool
                => $node instanceof Node\Stmt\ClassMethod
                && in_array($parameter, $node->params, true));
            $doc = $method?->getDocComment()?->getText() ?? '';
            $paramName = '$' . $parameterName->name;
            $documented = preg_match('/@param\\s+[^\\s]+\\s+' . preg_quote($paramName, '/') . '\\s+([^\\r\\n]+)/', $doc, $matches) === 1
                && trim(trim($matches[1]), '* ') !== '';

            if (!$this->hasDescription($parameterComment) && !$documented) {
                $failures[] = $filename . ':' . $parameter->getStartLine() . ': Param requires PHPDoc description prose';
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
