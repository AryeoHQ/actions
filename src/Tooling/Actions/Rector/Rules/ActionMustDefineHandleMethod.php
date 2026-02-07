<?php

declare(strict_types=1);

namespace Tooling\Actions\Rector\Rules;

use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\TraitUse;
use Rector\Rector\AbstractRector;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

class ActionMustDefineHandleMethod extends AbstractRector
{
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    public function refactor(Node $node): null|Node
    {
        if (! $node instanceof Class_) {
            return null;
        }

        $implementsAction = $this->implementsActionContract($node);
        $usesAsAction = $this->usesAsActionTrait($node);

        // If class implements Action or uses AsAction, ensure it has handle() method
        if ($implementsAction || $usesAsAction) {
            return $this->ensureHandleMethod($node);
        }

        return null;
    }

    private function implementsActionContract(Class_ $node): bool
    {
        if ($node->implements === []) {
            return false;
        }

        foreach ($node->implements as $interface) {
            if ($interface instanceof FullyQualified && $interface->toString() === Action::class) {
                return true;
            }

            if ($interface->toString() === 'Action') {
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
            if ($stmt instanceof TraitUse) {
                foreach ($stmt->traits as $trait) {
                    if ($trait instanceof FullyQualified && $trait->toString() === AsAction::class) {
                        return true;
                    }

                    if ($trait->toString() === 'AsAction') {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function ensureHandleMethod(Class_ $node): null|Class_
    {
        if ($node->stmts === []) {
            return $this->addHandleMethod($node);
        }

        // Check if handle() method already exists
        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof ClassMethod && $stmt->name->toString() === 'handle') {
                return null;
            }
        }

        return $this->addHandleMethod($node);
    }

    private function addHandleMethod(Class_ $node): Class_
    {
        $handleMethod = new ClassMethod('handle', [
            'flags' => Modifiers::PUBLIC,
            'stmts' => [],
        ]);

        $node->stmts[] = $handleMethod;

        return $node;
    }
}
