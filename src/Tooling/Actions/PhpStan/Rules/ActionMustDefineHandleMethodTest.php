<?php

declare(strict_types=1);

namespace Tooling\Actions\PhpStan\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/** @extends RuleTestCase<ActionMustDefineHandleMethod> */
#[CoversClass(ActionMustDefineHandleMethod::class)]
class ActionMustDefineHandleMethodTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ActionMustDefineHandleMethod;
    }

    private function getFixturePath(string $filename): string
    {
        return __DIR__.'/../../../../../tests/Fixtures/Tooling/'.$filename;
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
                '`Action` instances must implement `handle()`.',
                10,
            ],
        ]);
    }
}
