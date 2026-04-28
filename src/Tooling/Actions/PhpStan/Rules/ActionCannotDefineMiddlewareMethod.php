<?php

declare(strict_types=1);

namespace Tooling\Actions\PhpStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use Support\Actions\Contracts\Action;
use Tooling\Rules\Attributes\NodeType;

/**
 * @extends \Tooling\PhpStan\Rules\Rule<Class_>
 */
#[NodeType(Class_::class)]
final class ActionCannotDefineMiddlewareMethod extends \Tooling\PhpStan\Rules\Rule
{
    /**
     * @param  Class_  $node
     */
    public function shouldHandle(Node $node, Scope $scope): bool
    {
        return $this->inherits($node, Action::class) && $this->hasMethod($node, 'middleware');
    }

    /**
     * @param  Class_  $node
     */
    public function handle(Node $node, Scope $scope): void
    {
        $this->error(
            sprintf(
                '`%s` instances cannot define a `middleware()` method. Use the `$middleware` property or `prepare()` instead.',
                class_basename(Action::class),
            ),
            $this->findMiddlewareMethodLine($node) ?? $node->name?->getStartLine() ?? $node->getStartLine(),
            'Action.middleware.method.notAllowed'
        );
    }

    private function findMiddlewareMethodLine(Class_ $node): null|int
    {
        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof ClassMethod && $stmt->name->toString() === 'middleware') {
                return $stmt->getStartLine();
            }
        }

        return null;
    }
}
