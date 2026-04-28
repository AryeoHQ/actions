<?php

declare(strict_types=1);

namespace Tooling\Actions\PhpStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\ObjectType;
use Support\Actions\Attributes\DispatchAfterQueuedFailed;
use Support\Actions\Contracts\Action;
use Tooling\Rules\Attributes\NodeType;

/**
 * @extends \Tooling\PhpStan\Rules\Rule<Class_>
 */
#[NodeType(Class_::class)]
final class DispatchAfterQueuedFailedRequiresTries extends \Tooling\PhpStan\Rules\Rule
{
    /**
     * @param  Class_  $node
     */
    public function shouldHandle(Node $node, Scope $scope): bool
    {
        return $this->inherits($node, Action::class)
            && $this->hasAttribute($node, DispatchAfterQueuedFailed::class)
            && ! $this->classHasProperty($node, 'tries');
    }

    /**
     * @param  Class_  $node
     */
    public function handle(Node $node, Scope $scope): void
    {
        $this->error(
            sprintf(
                '`%s` instances using `#[%s]` must define a `$tries` property.',
                class_basename(Action::class),
                class_basename(DispatchAfterQueuedFailed::class),
            ),
            $node->name?->getStartLine() ?? $node->getStartLine(),
            'Action.DispatchAfterQueuedFailed.tries.required'
        );
    }

    private function classHasProperty(Class_ $node, string $property): bool
    {
        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\Property) {
                foreach ($stmt->props as $prop) {
                    if ($prop->name->toString() === $property) {
                        return true;
                    }
                }
            }
        }

        $className = $node->namespacedName?->toString();

        if ($className === null) {
            return false;
        }

        $classReflection = (new ObjectType($className))->getClassReflection();

        if (! $classReflection instanceof ClassReflection) {
            return false;
        }

        return $classReflection->hasNativeProperty($property);
    }
}
