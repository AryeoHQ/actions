<?php

declare(strict_types=1);

namespace Tooling\Actions\Rector\Rules;

use Illuminate\Foundation\Bus\Dispatchable;
use PhpParser\Node;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\TraitUse;
use Rector\Rector\AbstractRector;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

class ActionCannotUseDispatchable extends AbstractRector
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

        // If class implements Action or uses AsAction, remove Dispatchable trait
        if ($implementsAction || $usesAsAction) {
            return $this->removeDispatchableTrait($node);
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

    private function removeDispatchableTrait(Class_ $node): null|Class_
    {
        if ($node->stmts === []) {
            return null;
        }

        $modified = false;

        foreach ($node->stmts as $key => $stmt) {
            if ($stmt instanceof TraitUse) {
                $filteredTraits = [];

                foreach ($stmt->traits as $trait) {
                    $isDispatchable = ($trait instanceof FullyQualified && $trait->toString() === Dispatchable::class)
                        || $trait->toString() === 'Dispatchable';

                    if (! $isDispatchable) {
                        $filteredTraits[] = $trait;
                    } else {
                        $modified = true;
                    }
                }

                // If all traits were removed, remove the entire TraitUse statement
                if ($filteredTraits === []) {
                    unset($node->stmts[$key]);
                } else {
                    $stmt->traits = $filteredTraits;
                }
            }
        }

        // Re-index array after unsetting
        if ($modified) {
            $node->stmts = array_values($node->stmts);

            return $node;
        }

        return null;
    }
}
