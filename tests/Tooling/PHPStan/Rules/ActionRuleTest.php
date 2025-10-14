<?php

declare(strict_types=1);

namespace Tests\Tooling\Actions\PHPStan\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Tooling\Actions\PHPStan\Rules\ActionRule;

#[CoversClass(ActionRule::class)]
class ActionRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ActionRule;
    }

    private function getFixturePath(string $filename): string
    {
        return __DIR__.'/../../../Fixtures/Variations/'.$filename;
    }

    #[Test]
    public function it_passes_valid_action_class(): void
    {
        $this->analyse([$this->getFixturePath('ValidAction.php')], []);
    }

    #[Test]
    public function it_fails_action_class_not_final(): void
    {
        $this->analyse([$this->getFixturePath('NotFinalAction.php')], [
            [
                'Action classes must be final.',
                10,
            ],
        ]);
    }

    #[Test]
    public function it_fails_action_class_missing_execute_method(): void
    {
        $this->analyse([$this->getFixturePath('MissingExecuteMethodAction.php')], [
            [
                'Action classes must implement the execute() method.',
                10,
            ],
        ]);
    }

    #[Test]
    public function it_fails_action_class_missing_as_action_trait(): void
    {
        $this->analyse([$this->getFixturePath('MissingAsActionTraitAction.php')], [
            [
                'Action classes must use the Support\Actions\Concerns\AsAction trait.',
                9,
            ],
        ]);
    }

    #[Test]
    public function it_fails_action_class_missing_all_requirements(): void
    {
        $this->analyse([$this->getFixturePath('MissingAllRequirementsAction.php')], [
            [
                'Action classes must be final.',
                9,
            ],
            [
                'Action classes must implement the execute() method.',
                9,
            ],
            [
                'Action classes must use the Support\Actions\Concerns\AsAction trait.',
                9,
            ],
        ]);
    }

    #[Test]
    public function it_ignores_non_action_class(): void
    {
        $this->analyse([$this->getFixturePath('NonActionClass.php')], []);
    }

    #[Test]
    public function it_passes_action_class_with_all_requirements(): void
    {
        $this->analyse([$this->getFixturePath('CompleteAction.php')], []);
    }
}
