<?php

declare(strict_types=1);

namespace Tests\Tooling\Actions\PhpStan\Rules;

use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tooling\Actions\PhpStan\Rules\ActionMustBeFinal;

/**
 * @extends RuleTestCase<ActionMustBeFinal>
 */
#[CoversClass(ActionMustBeFinal::class)]
class ActionMustBeFinalTest extends RuleTestCase
{
    protected function getRule(): ActionMustBeFinal
    {
        return new ActionMustBeFinal;
    }

    private function getFixturePath(string $filename): string
    {
        return __DIR__.'/../../Fixtures/Variations/'.$filename;
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
}
