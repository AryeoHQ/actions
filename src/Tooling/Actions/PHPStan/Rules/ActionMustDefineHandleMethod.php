<?php

declare(strict_types=1);

namespace Tooling\Actions\PhpStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Support\Actions\Contracts\Action;

/**
 * @implements Rule<Class_>
 */
final class ActionMustDefineHandleMethod implements Rule
{
    public function getNodeType(): string
    {
        return Class_::class;
    }

    /**
     * @param  Class_  $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $this->implementsActionContract($node)) {
            return [];
        }

        if ($this->hasHandleMethod($node)) {
            return [];
        }

        return [
            RuleErrorBuilder::message('`Action` instances must implement `handle()`.')
                ->line($node->getStartLine())
                ->identifier('actions.handle')
                ->build(),
        ];
    }

    private function implementsActionContract(Class_ $node): bool
    {
        if ($node->implements === []) {
            return false;
        }

        foreach ($node->implements as $interface) {
            if ($interface instanceof FullyQualified) {
                if ($interface->toString() === Action::class) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasHandleMethod(Class_ $node): bool
    {
        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\ClassMethod && $stmt->name->name === 'handle') {
                return true;
            }
        }

        return false;
    }
}
