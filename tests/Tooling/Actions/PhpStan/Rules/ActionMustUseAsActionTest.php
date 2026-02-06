<?php

declare(strict_types=1);

namespace Tests\Tooling\Actions\PhpStan\Rules;

use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tooling\Actions\PhpStan\Rules\ActionMustUseAsAction;

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
        return __DIR__.'/../../Fixtures/Variations/'.$filename;
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
    public function it_ignores_non_action_classes(): void
    {
        $this->analyse([$this->getFixturePath('NonActionClass.php')], []);
    }
}
