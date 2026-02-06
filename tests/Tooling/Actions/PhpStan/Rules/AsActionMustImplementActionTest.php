<?php

declare(strict_types=1);

namespace Tests\Tooling\Actions\PhpStan\Rules;

use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tooling\Actions\PhpStan\Rules\AsActionMustImplementAction;

/** @extends RuleTestCase<AsActionMustImplementAction> */
#[CoversClass(AsActionMustImplementAction::class)]
class AsActionMustImplementActionTest extends RuleTestCase
{
    protected function getRule(): AsActionMustImplementAction
    {
        return new AsActionMustImplementAction;
    }

    private function getFixturePath(string $filename): string
    {
        return __DIR__.'/../../Fixtures/Variations/'.$filename;
    }

    #[Test]
    public function it_passes_when_class_uses_trait_and_implements_interface(): void
    {
        $this->analyse([$this->getFixturePath('ValidAction.php')], []);
    }

    #[Test]
    public function it_fails_when_class_uses_trait_but_not_interface(): void
    {
        $this->analyse([$this->getFixturePath('MissingActionContractAction.php')], [
            [
                '`AsAction` trait requires `Action` contract.',
                9,
            ],
        ]);
    }

    #[Test]
    public function it_ignores_classes_not_using_trait(): void
    {
        $this->analyse([$this->getFixturePath('NonActionClass.php')], []);
    }
}
