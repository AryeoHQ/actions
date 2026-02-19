<?php

declare(strict_types=1);

namespace Tests\Tooling\Actions\Rector\Rules;

use Illuminate\Foundation\Bus\Dispatchable;
use PhpParser\Node\Stmt\Class_;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Tooling\Concerns\GetsFixtures;
use Tooling\Actions\Rector\Rules\ActionCannotUseDispatchable;
use Tooling\Rector\Rules\Provides\ValidatesInheritance;
use Tooling\Rector\Testing\ParsesNodes;
use Tooling\Rector\Testing\ResolvesRectorRules;

#[CoversClass(ActionCannotUseDispatchable::class)]
class ActionCannotUseDispatchableTest extends TestCase
{
    use GetsFixtures;
    use ParsesNodes;
    use ResolvesRectorRules;
    use ValidatesInheritance;

    #[Test]
    public function removes_dispatchable_trait_from_action(): void
    {
        $classNode = $this->getClassNode($this->getFixturePath('ActionWithDispatchable.php'));

        $this->assertTrue($this->inherits($classNode, Dispatchable::class));

        $rule = $this->resolveRule(ActionCannotUseDispatchable::class);
        $result = $rule->refactor($classNode);

        $this->assertInstanceOf(Class_::class, $result);
        $this->assertFalse($this->inherits($result, Dispatchable::class));
    }

    #[Test]
    public function does_not_modify_action_without_dispatchable(): void
    {
        $classNode = $this->getClassNode($this->getFixturePath('ValidAction.php'));

        $this->assertTrue($this->doesNotInherit($classNode, Dispatchable::class));

        $rule = $this->resolveRule(ActionCannotUseDispatchable::class);
        $result = $rule->refactor($classNode);

        $this->assertNull($result);
    }
}
