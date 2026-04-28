<?php

declare(strict_types=1);

namespace Tooling\Actions\PhpStan\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Actions\Contracts\Action;
use Tests\Tooling\Concerns\GetsFixtures;

/**
 * @extends RuleTestCase<ActionCannotDefineMiddlewareMethod>
 */
#[CoversClass(ActionCannotDefineMiddlewareMethod::class)]
class ActionCannotDefineMiddlewareMethodTest extends RuleTestCase
{
    use GetsFixtures;

    protected function getRule(): Rule
    {
        return new ActionCannotDefineMiddlewareMethod;
    }

    #[Test]
    public function it_passes_when_action_does_not_define_middleware_method(): void
    {
        $this->analyse([$this->getFixturePath('ValidAction.php')], []);
    }

    #[Test]
    public function it_fails_when_action_defines_middleware_method(): void
    {
        $this->analyse([$this->getFixturePath('ActionWithMiddlewareMethod.php')], [
            [
                sprintf(
                    '`%s` instances cannot define a `middleware()` method. Use the `$middleware` property or `prepare()` instead.',
                    class_basename(Action::class),
                ),
                15,
            ],
        ]);
    }
}
