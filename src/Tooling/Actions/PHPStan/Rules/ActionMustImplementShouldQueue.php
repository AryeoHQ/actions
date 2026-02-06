<?php

declare(strict_types=1);

namespace Tooling\Actions\PhpStan\Rules;

use Illuminate\Contracts\Queue\ShouldQueue;
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
final class ActionMustImplementShouldQueue implements Rule
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

        if ($this->implementsShouldQueue($node)) {
            return [];
        }

        return [
            RuleErrorBuilder::message('`Action` instances must implement `ShouldQueue`.')
                ->line($node->getStartLine())
                ->identifier('actions.shouldQueue')
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

    private function implementsShouldQueue(Class_ $node): bool
    {
        if ($node->implements === []) {
            return false;
        }

        foreach ($node->implements as $interface) {
            if ($interface instanceof FullyQualified) {
                if ($interface->toString() === ShouldQueue::class) {
                    return true;
                }
            }
        }

        return false;
    }
}
