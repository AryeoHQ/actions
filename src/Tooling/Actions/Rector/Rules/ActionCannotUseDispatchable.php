<?php

declare(strict_types=1);

namespace Tooling\Actions\Rector\Rules;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
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
#[Definition('Remove Dispatchable trait from Action classes')]
#[NodeType(Class_::class)]
#[Sample('tooling.actions.rector.rules.samples')]
class ActionCannotUseDispatchable extends \Tooling\Rector\Rules\Rule
{
    public function shouldHandle(Node $node): bool
    {
        return $this->inherits($node, [Action::class, AsAction::class])
            && $this->inherits($node, Dispatchable::class)
            && $this->doesNotInherit($node, Queueable::class);
    }

    public function handle(Node $node): null|Node
    {
        return $this->removeTrait($node, Dispatchable::class);
    }
}
