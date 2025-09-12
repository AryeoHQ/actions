<?php

declare(strict_types=1);

namespace Tests\Support\Actions\PHPStan\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Support\Actions\PHPStan\Rules\ActionRule;

#[CoversClass(ActionRule::class)]
class ActionRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ActionRule();
    }

    private function getFixturePath(string $filename): string
    {
        return __DIR__ . '/../../../../Fixtures/PHPStan/' . $filename;
    }

    #[Test]
    public function itPassesValidActionClass(): void
    {
        $this->analyse([$this->getFixturePath('ValidAction.php')], []);
    }

    #[Test]
    public function itFailsActionClassNotFinal(): void
    {
        $this->analyse([$this->getFixturePath('NotFinalAction.php')], [
            [
                'Action classes must be final.',
                8,
            ],
        ]);
    }

    #[Test]
    public function itFailsActionClassMissingExecuteMethod(): void
    {
        $this->analyse([$this->getFixturePath('MissingExecuteMethodAction.php')], [
            [
                'Action classes must implement the execute() method.',
                8,
            ],
        ]);
    }

    #[Test]
    public function itFailsActionClassMissingAsActionTrait(): void
    {
        $this->analyse([$this->getFixturePath('MissingAsActionTraitAction.php')], [
            [
                'Action classes must use the Support\Actions\Concerns\AsAction trait.',
                7,
            ],
        ]);
    }

    #[Test]
    public function itFailsActionClassMissingAllRequirements(): void
    {
        $this->analyse([$this->getFixturePath('MissingAllRequirementsAction.php')], [
            [
                'Action classes must be final.',
                7,
            ],
            [
                'Action classes must implement the execute() method.',
                7,
            ],
            [
                'Action classes must use the Support\Actions\Concerns\AsAction trait.',
                7,
            ],
        ]);
    }

    #[Test]
    public function itIgnoresNonActionClass(): void
    {
        $this->analyse([$this->getFixturePath('NonActionClass.php')], []);
    }

    #[Test]
    public function itPassesActionClassWithAllRequirements(): void
    {
        $this->analyse([$this->getFixturePath('CompleteAction.php')], []);
    }
}
