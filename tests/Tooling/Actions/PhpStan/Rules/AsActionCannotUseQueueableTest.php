<?php

declare(strict_types=1);

namespace Tests\Tooling\Actions\PhpStan\Rules;

use Illuminate\Foundation\Queue\Queueable;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tooling\Actions\PhpStan\Rules\AsActionCannotUseQueueable;

/**
 * @extends RuleTestCase<AsActionCannotUseQueueable>
 */
#[CoversClass(AsActionCannotUseQueueable::class)]
class AsActionCannotUseQueueableTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new AsActionCannotUseQueueable;
    }

    private function getVariationFixturePath(string $filename): string
    {
        return __DIR__.'/../../Fixtures/Variations/'.$filename;
    }

    #[Test]
    public function it_fails_when_as_action_uses_queueable_trait(): void
    {
        $this->analyse([$this->getVariationFixturePath('AsActionWithQueueable.php')], [
            [
                '`AsAction` trait cannot use the `' . Queueable::class . '` trait.',
                12,
            ],
        ]);
    }
}
