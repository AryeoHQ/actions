<?php

declare(strict_types=1);

namespace Tooling\Actions\PhpStan\Rules;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use PhpParser\Node;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\TraitUse;
use PHPStan\Analyser\Scope;
use Support\Actions\Contracts\Action;
use Tooling\Rules\Attributes\NodeType;

/**
 * @extends \Tooling\PhpStan\Rules\Rule<Class_>
 */
#[NodeType(Class_::class)]
final class ActionCannotUseDispatchable extends \Tooling\PhpStan\Rules\Rule
{
    /**
     * @param  Class_  $node
     */
    public function shouldHandle(Node $node, Scope $scope): bool
    {
        return $this->inherits($node, Action::class)
            && $this->inherits($node, Dispatchable::class)
            && $this->doesNotInherit($node, Queueable::class);
    }

    /**
     * @param  Class_  $node
     */
    public function handle(Node $node, Scope $scope): void
    {
        $this->error(
            '`Action` instances cannot use the `'.Dispatchable::class.'` trait.',
            $this->findDispatchableTraitLine($node) ?? $node->getStartLine(),
            'actions.dispatchable'
        );
    }

    private function findDispatchableTraitLine(Class_ $node): null|int
    {
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
