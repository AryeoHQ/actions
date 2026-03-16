<?php

declare(strict_types=1);

namespace Tooling\Actions\Rector\Rules;

use PhpParser\Node\Stmt\Class_;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Actions\Contracts\Action;
use Tests\TestCase;
use Tests\Tooling\Concerns\GetsFixtures;
use Tooling\Rector\Rules\Provides\ValidatesInheritance;
use Tooling\Rector\Testing\ParsesNodes;
use Tooling\Rector\Testing\ResolvesRectorRules;

#[CoversClass(AsActionMustImplementAction::class)]
class AsActionMustImplementActionTest extends TestCase
{
    use GetsFixtures;
    use ParsesNodes;
    use ResolvesRectorRules;
    use ValidatesInheritance;

    #[Test]
    public function adds_contract_when_trait_is_used(): void
    {
        $classNode = $this->getClassNode($this->getFixturePath('MissingActionContractAction.php'));

        $this->assertTrue($this->doesNotInherit($classNode, Action::class));

        $rule = $this->resolveRule(AsActionMustImplementAction::class);
        $result = $rule->refactor($classNode);

        $this->assertInstanceOf(Class_::class, $result);
        $this->assertTrue($this->inherits($result, Action::class));
    }

    #[Test]
    public function does_not_modify_complete_class(): void
    {
        $classNode = $this->getClassNode($this->getFixturePath('ValidAction.php'));

        $this->assertTrue($this->inherits($classNode, Action::class));

        $rule = $this->resolveRule(AsActionMustImplementAction::class);
        $result = $rule->refactor($classNode);

        $this->assertTrue($this->inherits($result, Action::class));
    }
}
