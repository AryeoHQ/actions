<?php

declare(strict_types=1);

namespace Tests\Tooling\Actions\PhpStan\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tooling\Actions\PhpStan\Rules\ActionCannotUseDispatchable;

/**
 * @extends RuleTestCase<ActionCannotUseDispatchable>
 */
#[CoversClass(ActionCannotUseDispatchable::class)]
class ActionCannotUseDispatchableTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ActionCannotUseDispatchable;
    }

    private function getFixturePath(string $filename): string
    {
        return __DIR__.'/../../Fixtures/Variations/'.$filename;
    }

    #[Test]
    public function it_passes_when_action_does_not_use_dispatchable(): void
    {
        $this->analyse([$this->getFixturePath('ValidAction.php')], []);
    }

    #[Test]
    public function it_fails_when_action_uses_dispatchable_trait(): void
    {
        $this->analyse([$this->getFixturePath('ActionWithDispatchable.php')], [
            [
                '`Action` instances cannot use the `Illuminate\Foundation\Bus\Dispatchable` trait.',
                14,
            ],
        ]);
    }

    #[Test]
    public function it_ignores_non_action_classes(): void
    {
        $this->analyse([$this->getFixturePath('NonActionClass.php')], []);
    }
}
