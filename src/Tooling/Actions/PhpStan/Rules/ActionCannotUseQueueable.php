<?php

declare(strict_types=1);

namespace Tooling\Actions\PhpStan\Rules;

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
final class ActionCannotUseQueueable extends \Tooling\PhpStan\Rules\Rule
{
    /**
     * @param  Class_  $node
     */
    public function shouldHandle(Node $node, Scope $scope): bool
    {
        return $this->inherits($node, Action::class) && $this->inherits($node, Queueable::class);
    }

    /**
     * @param  Class_  $node
     */
    public function handle(Node $node, Scope $scope): void
    {
        $this->error(
            sprintf(
                '`%s` instances cannot use the `%s` trait.',
                class_basename(Action::class),
                class_basename(Queueable::class),
            ),
            $this->findQueueableTraitLine($node) ?? $node->name?->getStartLine() ?? $node->getStartLine(),
            'Action.Queueable.notAllowed'
        );
    }

    private function findQueueableTraitLine(Class_ $node): null|int
    {
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
