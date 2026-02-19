<?php

declare(strict_types=1);

namespace Tooling\Actions\Rector\Rules;

use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;
use Tooling\Rector\Rules\Definitions\Attributes\Definition;
use Tooling\Rector\Rules\Samples\Attributes\Sample;
use Tooling\Rules\Attributes\NodeType;

/**
 * @extends \Tooling\Rector\Rules\Rule<\PhpParser\Node\Stmt\Class_>
 */
#[Definition('Make Action classes final')]
#[NodeType(Class_::class)]
#[Sample('tooling.actions.rector.rules.samples')]
class ActionMustBeFinal extends \Tooling\Rector\Rules\Rule
{
    public function shouldHandle(Node $node): bool
    {
        if ($node->isFinal()) {
            return false;
        }

        return $this->inherits($node, [Action::class, AsAction::class]);
    }

    public function handle(Node $node): null|Node
    {
        $node->flags |= Modifiers::FINAL;

        return $node;
    }
}
