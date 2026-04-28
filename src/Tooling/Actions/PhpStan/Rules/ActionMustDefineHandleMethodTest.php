<?php

declare(strict_types=1);

namespace Tooling\Actions\PhpStan\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Actions\Contracts\Action;
use Tests\Tooling\Concerns\GetsFixtures;

/** @extends RuleTestCase<ActionMustDefineHandleMethod> */
#[CoversClass(ActionMustDefineHandleMethod::class)]
class ActionMustDefineHandleMethodTest extends RuleTestCase
{
    use GetsFixtures;

    protected function getRule(): Rule
    {
        return new ActionMustDefineHandleMethod;
    }

    #[Test]
    public function it_passes_when_action_has_handle_method(): void
    {
        $this->analyse([$this->getFixturePath('ValidAction.php')], []);
    }

    #[Test]
    public function it_fails_when_action_class_is_missing_handle_method(): void
    {
        $this->analyse([$this->getFixturePath('MissingHandleMethodAction.php')], [
            [
                sprintf(
                    '`%s` instances must implement `handle()`.',
                    class_basename(Action::class),
                ),
                10,
            ],
        ]);
    }

    #[Test]
    public function it_fails_on_class_name_line_not_attribute_line(): void
    {
        $this->analyse([$this->getFixturePath('MissingHandleMethodActionWithAttribute.php')], [
            [
                sprintf(
                    '`%s` instances must implement `handle()`.',
                    class_basename(Action::class),
                ),
                11,
            ],
        ]);
    }
}
