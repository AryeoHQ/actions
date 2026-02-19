<?php

declare(strict_types=1);

namespace Tooling\Actions\PhpStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use Support\Actions\Contracts\Action;
use Tooling\Rules\Attributes\NodeType;

/**
 * @extends \Tooling\PhpStan\Rules\Rule<Class_>
 */
#[NodeType(Class_::class)]
final class ActionMustBeFinal extends \Tooling\PhpStan\Rules\Rule
{
    /**
     * @param  Class_  $node
     */
    public function shouldHandle(Node $node, Scope $scope): bool
    {
        return $this->inherits($node, Action::class) && ! $node->isFinal();
    }

    /**
     * @param  Class_  $node
     */
    public function handle(Node $node, Scope $scope): void
    {
        $this->error(
            '`Action` instances must be `final`.',
            $node->getStartLine(),
            'actions.final'
        );
    }
}
