<?php

declare(strict_types=1);

namespace Tooling\Actions\PhpStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;
use Support\Actions\Contracts\Action;
use Tooling\Rules\Attributes\NodeType;

/**
 * @extends \Tooling\PhpStan\Rules\Rule<MethodCall>
 */
#[NodeType(MethodCall::class)]
final class ActionHandleCannotBeCalledDirectly extends \Tooling\PhpStan\Rules\Rule
{
    /**
     * @param  MethodCall  $node
     */
    public function shouldHandle(Node $node, Scope $scope): bool
    {
        if (! $node->name instanceof Node\Identifier) {
            return false;
        }

        if ($node->name->name !== 'handle') {
            return false;
        }

        $callerType = $scope->getType($node->var);

        foreach ($callerType->getObjectClassNames() as $className) {
            $classReflection = $scope->getClassReflection();

            if ($classReflection !== null && $classReflection->getName() === $className) {
                // Allow calling handle() from within the same class
                return false;
            }

            $objectType = new ObjectType($className);
            $actionType = new ObjectType(Action::class);

            if ($actionType->isSuperTypeOf($objectType)->yes()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  MethodCall  $node
     */
    public function handle(Node $node, Scope $scope): void
    {
        $this->error(
            'Method handle() cannot be called directly on Action instances. Use now() or dispatch() instead.',
            $node->getStartLine(),
            'actions.handleDirectCall'
        );
    }
}
