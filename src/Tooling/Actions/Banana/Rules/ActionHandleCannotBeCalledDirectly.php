<?php

declare(strict_types=1);

namespace Tooling\Actions\PhpStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use Support\Actions\Contracts\Action;

/**
 * @implements Rule<MethodCall>
 */
final class ActionHandleCannotBeCalledDirectly implements Rule
{
    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @param  MethodCall  $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node->name instanceof Node\Identifier) {
            return [];
        }

        if ($node->name->name !== 'handle') {
            return [];
        }

        $callerType = $scope->getType($node->var);

        foreach ($callerType->getObjectClassNames() as $className) {
            $classReflection = $scope->getClassReflection();

            if ($classReflection !== null && $classReflection->getName() === $className) {
                // Allow calling handle() from within the same class
                return [];
            }

            $objectType = new ObjectType($className);
            $actionType = new ObjectType(Action::class);

            if ($actionType->isSuperTypeOf($objectType)->yes()) {
                return [
                    RuleErrorBuilder::message(
                        'Method handle() cannot be called directly on Action instances. Use now() or dispatch() instead.'
                    )
                        ->line($node->getStartLine())
                        ->identifier('actions.handleDirectCall')
                        ->build(),
                ];
            }
        }

        return [];
    }
}
