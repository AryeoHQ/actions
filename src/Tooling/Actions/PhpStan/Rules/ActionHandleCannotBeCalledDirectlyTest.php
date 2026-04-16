<?php

declare(strict_types=1);

namespace Tooling\Actions\PhpStan\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Tooling\Concerns\GetsFixtures;

/**
 * @extends RuleTestCase<ActionHandleCannotBeCalledDirectly>
 */
#[CoversClass(ActionHandleCannotBeCalledDirectly::class)]
class ActionHandleCannotBeCalledDirectlyTest extends RuleTestCase
{
    use GetsFixtures;

    protected function getRule(): Rule
    {
        return new ActionHandleCannotBeCalledDirectly;
    }

    #[Test]
    public function it_passes_for_valid_actions(): void
    {
        $this->analyse([$this->getFixturePath('ValidAction.php')], []);
    }

    #[Test]
    public function it_fails_when_handle_is_called_directly_on_action(): void
    {
        $this->analyse([$this->getFixturePath('CallingHandleDirectlyOnAction.php')], [
            [
                'Method handle() cannot be called directly on Action instances. Use now() or dispatch() instead.',
                12,
            ],
        ]);
    }
}
