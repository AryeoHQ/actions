<?php

declare(strict_types=1);

namespace Tooling\Actions\Rector\Rules;

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
#[Definition('Remove Queueable trait from Action classes')]
#[NodeType(Class_::class)]
#[Sample('tooling.actions.rector.rules.samples')]
class ActionCannotUseQueueable extends \Tooling\Rector\Rules\Rule
{
    public function shouldHandle(Node $node): bool
    {
        return $this->inherits($node, [Action::class, AsAction::class]);
    }

    public function handle(Node $node): null|Node
    {
        return $this->removeTrait($node, Queueable::class);
    }
}
