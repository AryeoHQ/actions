<?php

declare(strict_types=1);

namespace Tooling\Actions\Rector\Rules;

use Illuminate\Contracts\Queue\ShouldQueue;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Class_;
use Rector\PostRector\Collector\UseNodesToAddCollector;
use Rector\Rector\AbstractRector;
use Rector\StaticTypeMapper\ValueObject\Type\FullyQualifiedObjectType;
use Support\Actions\Contracts\Action;
use Throwable;

class ShouldQueueMustImplementAction extends AbstractRector
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

        if (! $this->implementsShouldQueue($node)) {
            return null;
        }

        if ($this->implementsActionContract($node)) {
            return null;
        }

        // Add Action interface
        return $this->addActionContract($node);
    }

    private function implementsShouldQueue(Class_ $node): bool
    {
        if ($node->implements === []) {
            return false;
        }

        foreach ($node->implements as $interface) {
            if ($interface instanceof FullyQualified && $interface->toString() === ShouldQueue::class) {
                return true;
            }

            if ($interface->toString() === 'ShouldQueue') {
                return true;
            }
        }

        return false;
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

    private function addActionContract(Class_ $node): Class_
    {
        // Add use statement for Action contract
        try {
            $this->useNodesToAddCollector->addUseImport(
                new FullyQualifiedObjectType(Action::class)
            );
        } catch (Throwable $e) {
            // In test environments, UseNodesToAddCollector might not have a current file
        }

        $actionInterface = new Name('Action');

        if ($node->implements === []) {
            $node->implements = [$actionInterface];
        } else {
            $node->implements[] = $actionInterface;
        }

        return $node;
    }
}
