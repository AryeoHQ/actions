<?php

declare(strict_types=1);

namespace Tooling\Actions\PhpStan\Rules;

use Illuminate\Queue\SerializesModels;
use PhpParser\Node;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Stmt\TraitUse;
use PHPStan\Analyser\Scope;
use Support\Actions\Concerns\AsAction;
use Tooling\Rules\Attributes\NodeType;

/**
 * @extends \Tooling\PhpStan\Rules\Rule<Trait_>
 */
#[NodeType(Trait_::class)]
final class AsActionCannotUseSerializesModels extends \Tooling\PhpStan\Rules\Rule
{
    /**
     * @param  Trait_  $node
     */
    public function shouldHandle(Node $node, Scope $scope): bool
    {
        return $node->namespacedName?->toString() === AsAction::class
            && $this->inherits($node, SerializesModels::class);
    }

    /**
     * @param  Trait_  $node
     */
    public function handle(Node $node, Scope $scope): void
    {
        $this->error(
            sprintf(
                '`%s` cannot use the `%s` trait. See README.md for details.',
                class_basename(AsAction::class),
                class_basename(SerializesModels::class),
            ),
            $this->findSerializesModelsTraitLine($node) ?? $node->name?->getStartLine() ?? $node->getStartLine(),
            'Action.SerializesModels.notAllowed'
        );
    }

    private function findSerializesModelsTraitLine(Trait_ $node): null|int
    {
        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof TraitUse) {
                foreach ($stmt->traits as $trait) {
                    if ($trait instanceof FullyQualified && $trait->toString() === SerializesModels::class) {
                        return $stmt->getStartLine();
                    }
                }
            }
        }

        return null;
    }
}
