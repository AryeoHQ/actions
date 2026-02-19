<?php

declare(strict_types=1);

namespace Tests\Tooling\Actions\Rector\Rules;

use Illuminate\Foundation\Queue\Queueable;
use PhpParser\Node\Stmt\Class_;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Tooling\Concerns\GetsFixtures;
use Tooling\Actions\Rector\Rules\ActionCannotUseQueueable;
use Tooling\Rector\Rules\Provides\ValidatesInheritance;
use Tooling\Rector\Testing\ParsesNodes;
use Tooling\Rector\Testing\ResolvesRectorRules;

#[CoversClass(ActionCannotUseQueueable::class)]
class ActionCannotUseQueueableTest extends TestCase
{
    use GetsFixtures;
    use ParsesNodes;
    use ResolvesRectorRules;
    use ValidatesInheritance;

    #[Test]
    public function removes_queueable_trait_from_action(): void
    {
        $classNode = $this->getClassNode($this->getFixturePath('ActionWithQueueable.php'));

        $this->assertTrue($this->inherits($classNode, Queueable::class));

        $rule = $this->resolveRule(ActionCannotUseQueueable::class);
        $result = $rule->refactor($classNode);

        $this->assertInstanceOf(Class_::class, $result);
        $this->assertFalse($this->inherits($result, Queueable::class));
    }

    #[Test]
    public function does_not_modify_action_without_queueable(): void
    {
        $classNode = $this->getClassNode($this->getFixturePath('ValidAction.php'));

        $this->assertTrue($this->doesNotInherit($classNode, Queueable::class));

        $rule = $this->resolveRule(ActionCannotUseQueueable::class);
        $result = $rule->refactor($classNode);

        $this->assertTrue($this->doesNotInherit($result, Queueable::class));
    }
}
