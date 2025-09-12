<?php

declare(strict_types=1);

namespace Support\Actions\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

/**
 * @implements Rule<Class_>
 */
final class ActionRule implements Rule
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
        $errors = [];

        if (! $this->implementsActionContract($node)) {
            return $errors;
        }

        if (! $node->isFinal()) {
            $errors[] = RuleErrorBuilder::message('Action classes must be final.')
                ->line($node->getStartLine())
                ->identifier('actions.final')
                ->build();
        }

        if (! $this->hasExecuteMethod($node)) {
            $errors[] = RuleErrorBuilder::message('Action classes must implement the execute() method.')
                ->line($node->getStartLine())
                ->identifier('actions.execute')
                ->build();
        }

        if (! $this->usesAsActionTrait($node)) {
            $errors[] = RuleErrorBuilder::message('Action classes must use the Support\Actions\Concerns\AsAction trait.')
                ->line($node->getStartLine())
                ->identifier('actions.trait')
                ->build();
        }

        return $errors;
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

    private function hasExecuteMethod(Class_ $node): bool
    {
        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\ClassMethod && $stmt->name->name === 'execute') {
                return true;
            }
        }

        return false;
    }

    private function usesAsActionTrait(Class_ $node): bool
    {
        if ($node->stmts === []) {
            return false;
        }

        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\TraitUse) {
                foreach ($stmt->traits as $trait) {
                    if ($trait instanceof FullyQualified) {
                        if ($trait->toString() === AsAction::class) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }
}
