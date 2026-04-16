<?php

declare(strict_types=1);

namespace Support\Actions\Bus;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Context;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Actions\Middleware\RunSucceededHook;
use Tests\Fixtures\Support\Orders\NonAction;
use Tests\Fixtures\Support\Orders\NonActionQueueable;
use Tests\TestCase;

#[CoversClass(Dispatcher::class)]
class DispatcherTest extends TestCase
{
    #[Test]
    public function it_passes_non_action_commands_through_dispatch_without_middleware_injection(): void
    {
        Bus::fake();

        $job = new NonActionQueueable;

        dispatch($job);

        $this->assertNotContains(RunSucceededHook::class, $job->middleware);
    }

    #[Test]
    public function it_passes_non_action_commands_through_dispatch_now_without_hooks(): void
    {
        $job = new NonActionQueueable;

        $this->app->make(\Illuminate\Contracts\Bus\Dispatcher::class)->dispatchNow($job);

        $this->assertSame([NonActionQueueable::class], Context::get('execution_log'));
    }

    #[Test]
    public function it_delegates_dispatch_sync(): void
    {
        $job = new NonAction;

        $this->app->make(\Illuminate\Contracts\Bus\Dispatcher::class)->dispatchSync($job);

        $this->assertSame([NonAction::class], Context::get('execution_log'));
    }

    #[Test]
    public function it_forwards_unknown_methods_to_decorated_dispatcher(): void
    {
        $dispatcher = $this->app->make(\Illuminate\Contracts\Bus\Dispatcher::class);

        $this->assertInstanceOf(Dispatcher::class, $dispatcher);

        $this->assertFalse($dispatcher->hasCommandHandler(new NonActionQueueable));
    }
}
