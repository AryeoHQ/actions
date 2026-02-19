<?php

declare(strict_types=1);

namespace Tests\Tooling\Actions\Rector\Rules;

use PhpParser\Node\Stmt\Class_;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Actions\Concerns\AsAction;
use Tests\TestCase;
use Tests\Tooling\Concerns\GetsFixtures;
use Tooling\Actions\Rector\Rules\ActionMustUseAsAction;
use Tooling\Rector\Rules\Provides\ValidatesInheritance;
use Tooling\Rector\Testing\ParsesNodes;
use Tooling\Rector\Testing\ResolvesRectorRules;

#[CoversClass(ActionMustUseAsAction::class)]
class ActionMustUseAsActionTest extends TestCase
{
    use GetsFixtures;
    use ParsesNodes;
    use ResolvesRectorRules;
    use ValidatesInheritance;

    #[Test]
    public function adds_trait_when_contract_is_implemented(): void
    {
        $classNode = $this->getClassNode($this->getFixturePath('MissingAsActionTraitAction.php'));

        $this->assertTrue($this->doesNotInherit($classNode, AsAction::class));

        $rule = $this->resolveRule(ActionMustUseAsAction::class);
        $result = $rule->refactor($classNode);

        $this->assertInstanceOf(Class_::class, $result);
        $this->assertTrue($this->inherits($result, AsAction::class));
    }

    #[Test]
    public function does_not_modify_complete_class(): void
    {
        $classNode = $this->getClassNode($this->getFixturePath('ValidAction.php'));

        $this->assertTrue($this->inherits($classNode, AsAction::class));

        $rule = $this->resolveRule(ActionMustUseAsAction::class);
        $result = $rule->refactor($classNode);

        $this->assertTrue($this->inherits($result, AsAction::class));
    }
}
