<?php

declare(strict_types=1);

namespace Tests\Tooling\Actions\PhpStan\Rules;

use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tooling\Actions\PhpStan\Rules\ShouldQueueMustImplementAction;

/**
 * @extends RuleTestCase<ShouldQueueMustImplementAction>
 */
#[CoversClass(ShouldQueueMustImplementAction::class)]
class ShouldQueueMustImplementActionTest extends RuleTestCase
{
    protected function getRule(): ShouldQueueMustImplementAction
    {
        return new ShouldQueueMustImplementAction;
    }

    private function getFixturePath(string $filename): string
    {
        return __DIR__.'/../../Fixtures/Variations/'.$filename;
    }

    #[Test]
    public function it_passes_when_should_queue_implements_action(): void
    {
        $this->analyse([$this->getFixturePath('ActionWithShouldQueue.php')], []);
    }

    #[Test]
    public function it_fails_when_should_queue_does_not_implement_action(): void
    {
        $this->analyse([$this->getFixturePath('ShouldQueueWithoutAction.php')], [
            [
                '`ShouldQueue` instances must implement `Action`.',
                9,
            ],
        ]);
    }

    #[Test]
    public function it_ignores_non_should_queue_classes(): void
    {
        $this->analyse([$this->getFixturePath('NonActionClass.php')], []);
    }
}
