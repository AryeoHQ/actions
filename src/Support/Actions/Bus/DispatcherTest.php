<?php

declare(strict_types=1);

namespace Support\Actions\Bus;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Context;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Actions\Contracts\Action;
use Support\Actions\Middleware\RunSucceeded;
use Tests\Fixtures\Support\Orders\Actions\Archive;
use Tests\Fixtures\Support\Orders\Actions\WithMiddleware;
use Tests\Fixtures\Support\Orders\Actions\WithSucceeded;
use Tests\Fixtures\Support\Orders\Actions\WithSucceededAndMiddleware;
use Tests\Fixtures\Support\Orders\Middleware\WritesToContext;
use Tests\Fixtures\Support\Orders\Middleware\WritesToContextBidirectional;
use Tests\Fixtures\Support\Orders\NonAction;
use Tests\Fixtures\Support\Orders\NonActionQueueable;
use Tests\Fixtures\Support\Orders\Order;
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

        $this->assertNotContains(RunSucceeded::class, $job->middleware);
    }

    #[Test]
    public function it_passes_non_action_commands_through_dispatch_now_without_hooks(): void
    {
        $job = new NonActionQueueable;

        $this->app->make(\Illuminate\Contracts\Bus\Dispatcher::class)->dispatchNow($job);

        $this->assertSame([NonActionQueueable::class], Context::get(Action::class));
    }

    #[Test]
    public function it_delegates_dispatch_sync(): void
    {
        $job = new NonAction;

        $this->app->make(\Illuminate\Contracts\Bus\Dispatcher::class)->dispatchSync($job);

        $this->assertSame([NonAction::class], Context::get(Action::class));
    }

    #[Test]
    public function it_forwards_unknown_methods_to_decorated_dispatcher(): void
    {
        $dispatcher = $this->app->make(\Illuminate\Contracts\Bus\Dispatcher::class);

        $this->assertInstanceOf(Dispatcher::class, $dispatcher);

        $this->assertFalse($dispatcher->hasCommandHandler(new NonActionQueueable));
    }

    #[Test]
    public function it_attaches_required_middleware_on_dispatch_sync(): void
    {
        $order = Order::factory()->make();

        $this->app->make(\Illuminate\Contracts\Bus\Dispatcher::class)->dispatchSync(WithSucceeded::make($order));

        $this->assertContains(WithSucceeded::class, Context::get(Action::class));
    }

    #[Test]
    public function it_calls_prepare_before_middleware_on_dispatch_sync(): void
    {
        $order = Order::factory()->make();

        $this->app->make(\Illuminate\Contracts\Bus\Dispatcher::class)->dispatchSync(WithMiddleware::make($order));

        $this->assertContains(WritesToContext::class, Context::get(Action::class));
    }

    #[Test]
    public function it_does_not_call_prepare_for_non_actions(): void
    {
        $job = new NonActionQueueable;

        $this->app->make(\Illuminate\Contracts\Bus\Dispatcher::class)->dispatchNow($job);

        $this->assertSame([NonActionQueueable::class], Context::get(Action::class));
    }

    #[Test]
    public function it_dispatches_now_without_prepare_when_action_does_not_define_it(): void
    {
        $order = Order::factory()->make();

        $result = Archive::make($order)->now();

        $this->assertEquals($order->name.': archived', $result);
    }

    #[Test]
    public function it_runs_lifecycle_in_correct_order_on_dispatch_sync(): void
    {
        $this->app->make(\Illuminate\Contracts\Bus\Dispatcher::class)->dispatchSync(WithSucceededAndMiddleware::make());

        $this->assertSame([
            WritesToContextBidirectional::IN,
            WithSucceededAndMiddleware::HANDLE,
            WritesToContextBidirectional::OUT,
            WithSucceededAndMiddleware::SUCCEEDED,
        ], Context::get(Action::class));
    }
}
