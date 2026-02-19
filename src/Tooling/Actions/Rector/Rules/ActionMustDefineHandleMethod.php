<?php

declare(strict_types=1);

namespace Tooling\Actions\Rector\Rules;

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
#[Definition('Add handle() method to Action classes')]
#[NodeType(Class_::class)]
#[Sample('tooling.actions.rector.rules.samples')]
class ActionMustDefineHandleMethod extends \Tooling\Rector\Rules\Rule
{
    public function shouldHandle(Node $node): bool
    {
        return $this->inherits($node, [Action::class, AsAction::class]);
    }

    public function handle(Node $node): null|Node
    {
        return $this->addMethod($node, 'handle', 'void');
    }
}
