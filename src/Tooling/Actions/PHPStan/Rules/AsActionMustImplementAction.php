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
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

/**
 * @implements Rule<Class_>
 */
final class AsActionMustImplementAction implements Rule
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
        $traitLine = $this->getAsActionTraitLine($node);

        if ($traitLine === null) {
            return [];
        }

        if ($this->implementsActionContract($node)) {
            return [];
        }

        return [
            RuleErrorBuilder::message('`AsAction` trait requires `Action` contract.')
                ->line($traitLine)
                ->identifier('actions.interface')
                ->build(),
        ];
    }

    private function getAsActionTraitLine(Class_ $node): null|int
    {
        if ($node->stmts === []) {
            return null;
        }

        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\TraitUse) {
                foreach ($stmt->traits as $trait) {
                    if ($trait instanceof FullyQualified) {
                        if ($trait->toString() === AsAction::class) {
                            return $stmt->getStartLine();
                        }
                    }
                }
            }
        }

        return null;
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
}
