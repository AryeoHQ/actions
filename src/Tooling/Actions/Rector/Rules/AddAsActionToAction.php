<?php

declare(strict_types=1);

namespace Tooling\Actions\Rector\Rules;

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

class AddAsActionToAction extends AbstractRector
{
    public function __construct(
        private UseNodesToAddCollector $useNodesToAddCollector
    ) {}

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

        // If Action contract is implemented, add AsAction trait if missing
        if ($implementsAction && ! $usesAsAction) {
            return $this->addAsActionTrait($node);
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
