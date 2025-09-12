<?php

declare(strict_types=1);

namespace Tests\Support\Actions\PHPStan\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
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

    public function test_it_passes_valid_action_class(): void
    {
        $this->analyse([$this->getFixturePath('ValidAction.php')], []);
    }

    public function test_it_fails_action_class_not_final(): void
    {
        $this->analyse([$this->getFixturePath('NotFinalAction.php')], [
            [
                'Action classes must be final.',
                8,
            ],
        ]);
    }

    public function test_it_fails_action_class_missing_execute_method(): void
    {
        $this->analyse([$this->getFixturePath('MissingExecuteMethodAction.php')], [
            [
                'Action classes must implement the execute() method.',
                8,
            ],
        ]);
    }

    public function test_it_fails_action_class_missing_as_action_trait(): void
    {
        $this->analyse([$this->getFixturePath('MissingAsActionTraitAction.php')], [
            [
                'Action classes must use the Support\Actions\Concerns\AsAction trait.',
                7,
            ],
        ]);
    }

    public function test_it_fails_action_class_missing_all_requirements(): void
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

    public function test_it_ignores_non_action_class(): void
    {
        $this->analyse([$this->getFixturePath('NonActionClass.php')], []);
    }

    public function test_it_passes_action_class_with_all_requirements(): void
    {
        $this->analyse([$this->getFixturePath('CompleteAction.php')], []);
    }
}
