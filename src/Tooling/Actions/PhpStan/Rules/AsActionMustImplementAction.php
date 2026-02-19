<?php

declare(strict_types=1);

namespace Tooling\Actions\PhpStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;
use Tooling\Rules\Attributes\NodeType;

/**
 * @extends \Tooling\PhpStan\Rules\Rule<Class_>
 */
#[NodeType(Class_::class)]
final class AsActionMustImplementAction extends \Tooling\PhpStan\Rules\Rule
{
    /**
     * @param  Class_  $node
     */
    public function shouldHandle(Node $node, Scope $scope): bool
    {
        return $this->inherits($node, AsAction::class)
            && $this->doesNotInherit($node, Action::class);
    }

    /**
     * @param  Class_  $node
     */
    public function handle(Node $node, Scope $scope): void
    {
        $traitLine = $this->getAsActionTraitLine($node);

        $this->error(
            '`AsAction` trait requires `Action` contract.',
            $traitLine,
            'actions.interface'
        );
    }

    private function getAsActionTraitLine(Class_ $node): null|int
    {
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
}
