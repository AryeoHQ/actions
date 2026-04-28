<?php

declare(strict_types=1);

namespace Tooling\Actions\PhpStan\Rules;

use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;
use Tests\Tooling\Concerns\GetsFixtures;

/** @extends RuleTestCase<AsActionMustImplementAction> */
#[CoversClass(AsActionMustImplementAction::class)]
class AsActionMustImplementActionTest extends RuleTestCase
{
    use GetsFixtures;

    protected function getRule(): AsActionMustImplementAction
    {
        return new AsActionMustImplementAction;
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
                sprintf(
                    '`%s` trait requires `%s` contract.',
                    class_basename(AsAction::class),
                    class_basename(Action::class),
                ),
                11,
            ],
        ]);
    }
}
