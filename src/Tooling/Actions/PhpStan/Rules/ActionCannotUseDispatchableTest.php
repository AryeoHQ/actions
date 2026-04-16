<?php

declare(strict_types=1);

namespace Tooling\Actions\PhpStan\Rules;

use Illuminate\Foundation\Bus\Dispatchable;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Tooling\Concerns\GetsFixtures;

/**
 * @extends RuleTestCase<ActionCannotUseDispatchable>
 */
#[CoversClass(ActionCannotUseDispatchable::class)]
class ActionCannotUseDispatchableTest extends RuleTestCase
{
    use GetsFixtures;

    protected function getRule(): Rule
    {
        return new ActionCannotUseDispatchable;
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
                '`Action` instances cannot use the `'.Dispatchable::class.'` trait.',
                14,
            ],
        ]);
    }

    #[Test]
    public function it_fails_on_trait_line_not_attribute_line(): void
    {
        $this->analyse([$this->getFixturePath('ActionWithDispatchableAndAttribute.php')], [
            [
                '`Action` instances cannot use the `'.Dispatchable::class.'` trait.',
                15,
            ],
        ]);
    }
}
