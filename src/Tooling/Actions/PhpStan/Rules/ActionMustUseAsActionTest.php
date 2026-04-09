<?php

declare(strict_types=1);

namespace Tooling\Actions\PhpStan\Rules;

use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/** @extends RuleTestCase<ActionMustUseAsAction> */
#[CoversClass(ActionMustUseAsAction::class)]
class ActionMustUseAsActionTest extends RuleTestCase
{
    protected function getRule(): ActionMustUseAsAction
    {
        return new ActionMustUseAsAction;
    }

    private function getFixturePath(string $filename): string
    {
        return __DIR__.'/../../../../../tests/Fixtures/Tooling/'.$filename;
    }

    #[Test]
    public function it_passes_when_action_uses_as_action_trait(): void
    {
        $this->analyse([$this->getFixturePath('ValidAction.php')], []);
    }

    #[Test]
    public function it_fails_when_action_class_is_missing_as_action_trait(): void
    {
        $this->analyse([$this->getFixturePath('MissingAsActionTraitAction.php')], [
            [
                '`Action` instances must use `AsAction`.',
                9,
            ],
        ]);
    }

    #[Test]
    public function it_fails_on_class_name_line_not_attribute_line(): void
    {
        $this->analyse([$this->getFixturePath('MissingAsActionTraitActionWithAttribute.php')], [
            [
                '`Action` instances must use `AsAction`.',
                10,
            ],
        ]);
    }
}
