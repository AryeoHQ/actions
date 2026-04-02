<?php

declare(strict_types=1);

namespace Tooling\Actions\Rector\Rules;

use Illuminate\Queue\SerializesModels;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tooling\Rector\Rules\Provides\ValidatesInheritance;
use Tooling\Rector\Testing\ParsesNodes;
use Tooling\Rector\Testing\ResolvesRectorRules;

#[CoversClass(AsActionCannotUseSerializesModels::class)]
class AsActionCannotUseSerializesModelsTest extends TestCase
{
    use ParsesNodes;
    use ResolvesRectorRules;
    use ValidatesInheritance;

    private function getSourcePath(string $filename): string
    {
        return __DIR__.'/../../../../../src/Support/Actions/Concerns/'.$filename;
    }

    #[Test]
    public function does_not_modify_as_action_without_serializes_models(): void
    {
        $traitNode = $this->getTraitNode($this->getSourcePath('AsAction.php'));

        $this->assertTrue($this->doesNotInherit($traitNode, SerializesModels::class));

        $rule = $this->resolveRule(AsActionCannotUseSerializesModels::class);
        $result = $rule->refactor($traitNode);

        $this->assertNull($result);
    }
}
