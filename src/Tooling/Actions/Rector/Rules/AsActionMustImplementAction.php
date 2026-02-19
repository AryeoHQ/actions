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
#[Definition('Add Action interface to classes using AsAction trait')]
#[NodeType(Class_::class)]
#[Sample('tooling.actions.rector.rules.samples')]
class AsActionMustImplementAction extends \Tooling\Rector\Rules\Rule
{
    public function shouldHandle(Node $node): bool
    {
        return $this->inherits($node, AsAction::class);
    }

    public function handle(Node $node): null|Node
    {
        return $this->addInterface($node, Action::class);
    }
}
