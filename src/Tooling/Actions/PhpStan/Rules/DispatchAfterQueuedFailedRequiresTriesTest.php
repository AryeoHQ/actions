<?php

declare(strict_types=1);

namespace Tooling\Actions\PhpStan\Rules;

use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Actions\Attributes\DispatchAfterQueuedFailed;
use Support\Actions\Contracts\Action;
use Tests\Tooling\Concerns\GetsFixtures;

/**
 * @extends RuleTestCase<DispatchAfterQueuedFailedRequiresTries>
 */
#[CoversClass(DispatchAfterQueuedFailedRequiresTries::class)]
class DispatchAfterQueuedFailedRequiresTriesTest extends RuleTestCase
{
    use GetsFixtures;

    protected function getRule(): DispatchAfterQueuedFailedRequiresTries
    {
        return new DispatchAfterQueuedFailedRequiresTries;
    }

    #[Test]
    public function it_passes_when_action_has_dispatch_after_queued_failed_and_tries(): void
    {
        $this->analyse([$this->getFixturePath('WithDispatchAfterQueuedFailedAndTries.php')], []);
    }

    #[Test]
    public function it_fails_when_action_has_dispatch_after_queued_failed_without_tries(): void
    {
        $this->analyse([$this->getFixturePath('WithDispatchAfterQueuedFailedWithoutTries.php')], [
            [
                sprintf(
                    '`%s` instances using `#[%s]` must define a `$tries` property.',
                    class_basename(Action::class),
                    class_basename(DispatchAfterQueuedFailed::class),
                ),
                12,
            ],
        ]);
    }

    #[Test]
    public function it_passes_when_action_does_not_have_dispatch_after_queued_failed(): void
    {
        $this->analyse([$this->getFixturePath('ValidAction.php')], []);
    }
}
