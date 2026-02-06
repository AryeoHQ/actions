<?php

declare(strict_types=1);

namespace Tests\Tooling\Actions\PhpStan\Rules;

use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tooling\Actions\PhpStan\Rules\ActionMustImplementShouldQueue;

/**
 * @extends RuleTestCase<ActionMustImplementShouldQueue>
 */
#[CoversClass(ActionMustImplementShouldQueue::class)]
class ActionMustImplementShouldQueueTest extends RuleTestCase
{
    protected function getRule(): ActionMustImplementShouldQueue
    {
        return new ActionMustImplementShouldQueue;
    }

    private function getFixturePath(string $filename): string
    {
        return __DIR__.'/../../Fixtures/Variations/'.$filename;
    }

    #[Test]
    public function it_passes_when_action_implements_should_queue(): void
    {
        $this->analyse([$this->getFixturePath('ActionWithShouldQueue.php')], []);
    }

    #[Test]
    public function it_fails_when_action_does_not_implement_should_queue(): void
    {
        $this->analyse([$this->getFixturePath('ActionWithoutShouldQueue.php')], [
            [
                '`Action` instances must implement `ShouldQueue`.',
                10,
            ],
        ]);
    }

    #[Test]
    public function it_ignores_non_action_classes(): void
    {
        $this->analyse([$this->getFixturePath('NonActionClass.php')], []);
    }
}
