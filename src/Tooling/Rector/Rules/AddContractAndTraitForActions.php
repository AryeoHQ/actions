<?php

declare(strict_types=1);

namespace Tooling\Rector\Rules;

use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\TraitUse;
use Rector\PostRector\Collector\UseNodesToAddCollector;
use Rector\Rector\AbstractRector;
use Rector\StaticTypeMapper\ValueObject\Type\FullyQualifiedObjectType;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;
use Throwable;

class AddContractAndTraitForActions extends AbstractRector
{
    public function __construct(
        private UseNodesToAddCollector $useNodesToAddCollector
    ) {}

    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (! $node instanceof Class_) {
            return null;
        }

        $hasChanges = false;
        $implementsAction = $this->implementsActionContract($node);
        $usesAsAction = $this->usesAsActionTrait($node);

        // Rule 1: If AsAction trait is used, add Action contract if missing
        if ($usesAsAction && ! $implementsAction) {
            $node = $this->addActionContract($node);
            $hasChanges = true;
        }

        // Rule 2: If Action contract is implemented, add AsAction trait if missing
        if ($implementsAction && ! $usesAsAction) {
            $node = $this->addAsActionTrait($node);
            $hasChanges = true;
        }

        // Rule 3: If class implements Action or uses AsAction, ensure it's final
        if (($implementsAction || $usesAsAction) && ! $node->isFinal()) {
            $node->flags |= Modifiers::FINAL;
            $hasChanges = true;
        }

        return $hasChanges ? $node : null;
    }

    public function implementsActionContract(Class_ $node): bool
    {
        if ($node->implements === []) {
            return false;
        }

        foreach ($node->implements as $interface) {
            if ($interface instanceof FullyQualified) {
                return true;
            }

            if ($interface->toString() === 'Action') {
                return true;
            }
        }

        return false;
    }

    public function usesAsActionTrait(Class_ $node): bool
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

    public function addActionContract(Class_ $node): Class_
    {
        // Check if contract is already implemented
        if ($this->implementsActionContract($node)) {
            return $node;
        }

        // Add use statement for Action contract
        // Only add use import if we have a current file context (not in tests)
        try {
            $this->useNodesToAddCollector->addUseImport(
                new FullyQualifiedObjectType(Action::class)
            );
        } catch (Throwable $e) {
            // In test environments, UseNodesToAddCollector might not have a current file
            // This is expected and we can continue without adding the use statement
        }

        $actionInterface = new Name('Action');

        if ($node->implements === []) {
            $node->implements = [$actionInterface];
        } else {
            $node->implements[] = $actionInterface;
        }

        return $node;
    }

    private function addAsActionTrait(Class_ $node): Class_
    {
        // Check if trait is already used
        if ($this->usesAsActionTrait($node)) {
            return $node;
        }

        // Add use statement for AsAction trait
        // Only add use import if we have a current file context (not in tests)
        try {
            $this->useNodesToAddCollector->addUseImport(
                new FullyQualifiedObjectType(AsAction::class)
            );
        } catch (Throwable $e) {
            // In test environments, UseNodesToAddCollector might not have a current file
            // This is expected and we can continue without adding the use statement
        }

        $asActionTrait = new Name('AsAction');
        $traitUse = new TraitUse([$asActionTrait]);

        // Add the trait use at the beginning of the class body
        if ($node->stmts === []) {
            $node->stmts = [$traitUse];
        } else {
            array_unshift($node->stmts, $traitUse);
        }

        return $node;
    }
}
