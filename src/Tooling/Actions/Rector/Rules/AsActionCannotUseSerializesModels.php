<?php

declare(strict_types=1);

namespace Tooling\Actions\Rector\Rules;

use Illuminate\Queue\SerializesModels;
use PhpParser\Node;
use PhpParser\Node\Stmt\Trait_;
use Support\Actions\Concerns\AsAction;
use Tooling\Rector\Rules\Definitions\Attributes\Definition;
use Tooling\Rector\Rules\Samples\Attributes\Sample;
use Tooling\Rules\Attributes\NodeType;

/**
 * @extends \Tooling\Rector\Rules\Rule<\PhpParser\Node\Stmt\Trait_>
 */
#[Definition('Remove SerializesModels trait from AsAction')]
#[NodeType(Trait_::class)]
#[Sample('tooling.actions.rector.rules.samples')]
class AsActionCannotUseSerializesModels extends \Tooling\Rector\Rules\Rule
{
    public function shouldHandle(Node $node): bool
    {
        return $this->isName($node, AsAction::class)
            && $this->inherits($node, SerializesModels::class);
    }

    public function handle(Node $node): null|Node
    {
        return $this->removeTrait($node, SerializesModels::class);
    }
}
