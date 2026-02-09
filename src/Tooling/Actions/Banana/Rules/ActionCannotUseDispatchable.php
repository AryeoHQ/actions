<?php

declare(strict_types=1);

namespace Tooling\Actions\PhpStan\Rules;

use Illuminate\Foundation\Bus\Dispatchable;
use PhpParser\Node;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\TraitUse;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Support\Actions\Contracts\Action;

/**
 * @implements Rule<Class_>
 */
final class ActionCannotUseDispatchable implements Rule
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

        $traitLine = $this->findDispatchableTraitLine($node);

        if ($traitLine === null) {
            return [];
        }

        return [
            RuleErrorBuilder::message('`Action` instances cannot use the `'.Dispatchable::class.'` trait.')
                ->line($traitLine)
                ->identifier('actions.dispatchable')
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

    private function findDispatchableTraitLine(Class_ $node): null|int
    {
        if ($node->stmts === []) {
            return null;
        }

        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof TraitUse) {
                foreach ($stmt->traits as $trait) {
                    if ($trait instanceof FullyQualified && $trait->toString() === Dispatchable::class) {
                        return $stmt->getStartLine();
                    }
                }
            }
        }

        return null;
    }
}
