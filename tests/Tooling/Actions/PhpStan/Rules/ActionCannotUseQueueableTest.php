<?php

declare(strict_types=1);

namespace Tests\Tooling\Actions\PhpStan\Rules;

use Illuminate\Foundation\Queue\Queueable;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tooling\Actions\PhpStan\Rules\ActionCannotUseQueueable;

/**
 * @extends RuleTestCase<ActionCannotUseQueueable>
 */
#[CoversClass(ActionCannotUseQueueable::class)]
class ActionCannotUseQueueableTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ActionCannotUseQueueable;
    }

    private function getFixturePath(string $filename): string
    {
        return __DIR__.'/../../../../Fixtures/Tooling/'.$filename;
    }

    #[Test]
    public function it_passes_when_action_does_not_use_queueable(): void
    {
        $this->analyse([$this->getFixturePath('ValidAction.php')], []);
    }

    #[Test]
    public function it_fails_when_action_uses_queueable_trait(): void
    {
        $this->analyse([$this->getFixturePath('ActionWithQueueable.php')], [
            [
                '`Action` instances cannot use the `'.Queueable::class.'` trait.',
                14,
            ],
        ]);
    }
}
