<?php

declare(strict_types=1);

namespace Tests\Tooling\Actions\Rector\Rules;

use PhpParser\Node\Stmt\Class_;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Tooling\Concerns\GetsFixtures;
use Tooling\Actions\Rector\Rules\ActionMustDefineHandleMethod;
use Tooling\Rector\Rules\Provides\ValidatesMethods;
use Tooling\Rector\Testing\ParsesNodes;
use Tooling\Rector\Testing\ResolvesRectorRules;

#[CoversClass(ActionMustDefineHandleMethod::class)]
class ActionMustDefineHandleMethodTest extends TestCase
{
    use GetsFixtures;
    use ParsesNodes;
    use ResolvesRectorRules;
    use ValidatesMethods;

    #[Test]
    public function adds_handle_method_to_action_without_it(): void
    {
        $classNode = $this->getClassNode($this->getFixturePath('MissingHandleMethodAction.php'));

        $this->assertTrue($this->doesNotHaveMethod($classNode, 'handle'));

        $rule = $this->resolveRule(ActionMustDefineHandleMethod::class);
        $result = $rule->refactor($classNode);

        $this->assertInstanceOf(Class_::class, $result);
        $this->assertTrue($this->hasMethod($result, 'handle'));
    }

    #[Test]
    public function does_not_modify_action_with_handle_method(): void
    {
        $classNode = $this->getClassNode($this->getFixturePath('ValidAction.php'));

        $this->assertTrue($this->hasMethod($classNode, 'handle'));

        $rule = $this->resolveRule(ActionMustDefineHandleMethod::class);
        $result = $rule->refactor($classNode);

        $this->assertTrue($this->hasMethod($result, 'handle'));
    }
}
