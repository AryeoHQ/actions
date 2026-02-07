<?php

declare(strict_types=1);

namespace Tests\Tooling\Actions\PhpStan\Rules;

use Illuminate\Foundation\Bus\Dispatchable;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tooling\Actions\PhpStan\Rules\AsActionCannotUseDispatchable;

/**
 * @extends RuleTestCase<AsActionCannotUseDispatchable>
 */
#[CoversClass(AsActionCannotUseDispatchable::class)]
class AsActionCannotUseDispatchableTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new AsActionCannotUseDispatchable;
    }

    private function getFixturePath(string $filename): string
    {
        return __DIR__.'/../../../../../src/Support/Actions/Concerns/AsAction.php';
    }

    private function getVariationFixturePath(string $filename): string
    {
        return __DIR__.'/../../Fixtures/Variations/'.$filename;
    }

    #[Test]
    public function it_fails_when_trait_uses_illuminate_dispatchable(): void
    {
        $this->analyse([$this->getVariationFixturePath('AsActionWithDispatchable.php')], [
            [
                '`AsAction` trait cannot use the `'.Dispatchable::class.'` trait.',
                12,
            ],
        ]);
    }

    #[Test]
    public function it_passes_when_as_action_uses_local_dispatchable_trait(): void
    {
        // AsAction uses the local Support\Actions\Concerns\Dispatchable trait,
        // not the Illuminate one, so it should pass
        $this->analyse([$this->getFixturePath('AsAction.php')], []);
    }
}
