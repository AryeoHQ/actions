<?php

declare(strict_types=1);

namespace Tooling\Actions\PhpStan\Rules;

use Illuminate\Foundation\Queue\Queueable;
use PhpParser\Node;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Stmt\TraitUse;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Support\Actions\Concerns\AsAction;

/**
 * @implements Rule<Trait_>
 */
final class AsActionCannotUseQueueable implements Rule
{
    public function getNodeType(): string
    {
        return Trait_::class;
    }

    /**
     * @param  Trait_  $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $this->isAsActionTrait($node, $scope)) {
            return [];
        }

        $traitLine = $this->findQueueableTraitLine($node);

        if ($traitLine === null) {
            return [];
        }

        return [
            RuleErrorBuilder::message('`AsAction` trait cannot use the `Illuminate\Foundation\Queue\Queueable` trait.')
                ->line($traitLine)
                ->identifier('asAction.queueable')
                ->build(),
        ];
    }

    private function isAsActionTrait(Trait_ $node, Scope $scope): bool
    {
        if ($node->namespacedName === null) {
            return false;
        }

        return $node->namespacedName->toString() === AsAction::class;
    }

    private function findQueueableTraitLine(Trait_ $node): ?int
    {
        if ($node->stmts === []) {
            return null;
        }

        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof TraitUse) {
                foreach ($stmt->traits as $trait) {
                    if ($trait instanceof FullyQualified && $trait->toString() === Queueable::class) {
                        return $stmt->getStartLine();
                    }
                }
            }
        }

        return null;
    }
}
