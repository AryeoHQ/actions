<?php

declare(strict_types=1);

namespace Tooling\Actions\PhpStan\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * @extends RuleTestCase<AsActionCannotUseSerializesModels>
 */
#[CoversClass(AsActionCannotUseSerializesModels::class)]
class AsActionCannotUseSerializesModelsTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new AsActionCannotUseSerializesModels;
    }

    private function getSourcePath(string $filename): string
    {
        return __DIR__.'/../../../../../src/Support/Actions/Concerns/'.$filename;
    }

    #[Test]
    public function it_passes_when_as_action_does_not_use_serializes_models(): void
    {
        $this->analyse([$this->getSourcePath('AsAction.php')], []);
    }
}
