<?php

declare(strict_types=1);

namespace Tooling\Actions\PhpStan\Rules;

use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Tooling\Concerns\GetsFixtures;

/**
 * @extends RuleTestCase<ActionMustBeFinal>
 */
#[CoversClass(ActionMustBeFinal::class)]
class ActionMustBeFinalTest extends RuleTestCase
{
    use GetsFixtures;

    protected function getRule(): ActionMustBeFinal
    {
        return new ActionMustBeFinal;
    }

    #[Test]
    public function it_passes_for_final_action_class(): void
    {
        $this->analyse([$this->getFixturePath('ValidAction.php')], []);
    }

    #[Test]
    public function it_fails_when_action_class_is_not_final(): void
    {
        $this->analyse([$this->getFixturePath('NotFinalAction.php')], [
            [
                '`Action` instances must be `final`.',
                10,
            ],
        ]);
    }

    #[Test]
    public function it_fails_on_class_name_line_not_attribute_line(): void
    {
        $this->analyse([$this->getFixturePath('NotFinalActionWithAttribute.php')], [
            [
                '`Action` instances must be `final`.',
                11,
            ],
        ]);
    }
}
