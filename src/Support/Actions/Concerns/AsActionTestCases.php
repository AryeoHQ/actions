<?php

declare(strict_types=1);

namespace Support\Actions\Concerns;

use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Context;
use PHPUnit\Framework\Attributes\Test;
use Support\Actions\Contracts\Action;
use Tests\Fixtures\Support\Orders\Actions\Notify;
use Tests\Fixtures\Support\Orders\Actions\WithSucceededAndMiddleware;
use Tests\Fixtures\Support\Orders\Middleware\WritesToContextBidirectional;

/** @mixin \Tests\TestCase */
trait AsActionTestCases
{
    use DispatchableTestCases;
    use HasLifecycleTestCases;
    use NowableTestCases;

    #[Test]
    public function it_is_makeable(): void
    {
        $action = Notify::make();

        $this->assertInstanceOf(Notify::class, $action);
    }

    #[Test]
    public function it_does_not_use_serializes_models(): void
    {
        $traits = class_uses_recursive(AsAction::class);

        $this->assertNotContains(
            SerializesModels::class,
            $traits,
            'AsAction must not include SerializesModels. This is a critical design decision to preserve model state at dispatch time. See README.md for details.',
        );
    }

    #[Test]
    public function it_runs_full_lifecycle_when_now_then_dispatch(): void
    {
        $action = WithSucceededAndMiddleware::make();

        $action->now();

        $expectedLifecycle = [
            WritesToContextBidirectional::IN,
            WithSucceededAndMiddleware::HANDLE,
            WritesToContextBidirectional::OUT,
            WithSucceededAndMiddleware::SUCCEEDED,
        ];

        $this->assertSame($expectedLifecycle, Context::get(Action::class));

        Context::forget(Action::class);

        $action->dispatch();

        $this->assertSame($expectedLifecycle, Context::get(Action::class));
    }

    #[Test]
    public function it_runs_full_lifecycle_when_dispatch_then_now(): void
    {
        $action = WithSucceededAndMiddleware::make();

        $action->dispatch();

        $expectedLifecycle = [
            WritesToContextBidirectional::IN,
            WithSucceededAndMiddleware::HANDLE,
            WritesToContextBidirectional::OUT,
            WithSucceededAndMiddleware::SUCCEEDED,
        ];

        $this->assertSame($expectedLifecycle, Context::get(Action::class));

        Context::forget(Action::class);

        $action->now();

        $this->assertSame($expectedLifecycle, Context::get(Action::class));
    }
}
