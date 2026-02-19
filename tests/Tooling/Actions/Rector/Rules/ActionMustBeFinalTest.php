<?php

declare(strict_types=1);

namespace Tests\Tooling\Actions\Rector\Rules;

use PhpParser\Node\Stmt\Class_;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Tooling\Concerns\GetsFixtures;
use Tooling\Actions\Rector\Rules\ActionMustBeFinal;
use Tooling\Rector\Testing\ParsesNodes;
use Tooling\Rector\Testing\ResolvesRectorRules;

#[CoversClass(ActionMustBeFinal::class)]
class ActionMustBeFinalTest extends TestCase
{
    use GetsFixtures;
    use ParsesNodes;
    use ResolvesRectorRules;

    #[Test]
    public function makes_action_class_final(): void
    {
        $classNode = $this->getClassNode($this->getFixturePath('NotFinalAction.php'));

        $this->assertNotNull($classNode, 'Should find a class node');
        $this->assertFalse($classNode->isFinal(), 'Class should not be final initially');

        $rule = $this->resolveRule(ActionMustBeFinal::class);
        $result = $rule->refactor($classNode);

        $this->assertInstanceOf(Class_::class, $result);
        $this->assertTrue($result->isFinal(), 'Action class should be made final');
    }

    #[Test]
    public function does_not_modify_already_final_class(): void
    {
        $classNode = $this->getClassNode($this->getFixturePath('ValidAction.php'));

        $this->assertNotNull($classNode, 'Should find a class node');

        $rule = $this->resolveRule(ActionMustBeFinal::class);
        $result = $rule->refactor($classNode);

        $this->assertNull($result);
    }
}
