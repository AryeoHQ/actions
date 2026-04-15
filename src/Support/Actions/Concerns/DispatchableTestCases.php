<?php

declare(strict_types=1);

namespace Support\Actions\Concerns;

use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Facades\Context;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Support\Orders\Actions\Archive;
use Tests\Fixtures\Support\Orders\Actions\WithFailedAndSucceeded;
use Tests\Fixtures\Support\Orders\Actions\WithSucceeded;
use Tests\Fixtures\Support\Orders\Middleware\WritesToContext;
use Tests\Fixtures\Support\Orders\Order;

trait DispatchableTestCases
{
    #[Test]
    public function it_returns_pending_dispatch_when_dispatched(): void
    {
        Archive::fake();
        $order = Order::factory()->make();

        $pendingDispatch = Archive::make($order)->dispatch();

        $this->assertInstanceOf(PendingDispatch::class, $pendingDispatch);
    }

    #[Test]
    public function it_can_be_dispatched_to_the_queue(): void
    {
        Archive::fake();
        $order = Order::factory()->make();

        Archive::make($order)->dispatch();

        Archive::assertFired();
    }

    #[Test]
    public function you_can_confirm_it_is_dispatched_to_the_queue_using_a_callback(): void
    {
        Archive::fake();
        $order = Order::factory()->make();

        Archive::make($order)->dispatch();

        Archive::assertFired(
            fn (Archive $action) => $action->order->name === $order->name
        );
    }

    #[Test]
    public function you_can_confirm_it_was_not_dispatched_to_the_queue_using_a_callback(): void
    {
        Archive::fake();
        $orders = Order::factory()->times(2)->make();

        Archive::make($orders->first())->dispatch();

        Archive::assertNotFired(
            fn (Archive $action) => $action->order->name === $orders->last()->name
        );
    }

    #[Test]
    public function it_can_be_conditionally_dispatched_to_the_queue(): void
    {
        Archive::fake();
        $order = Order::factory()->make();

        Archive::make($order)->dispatchIf(true);

        Archive::assertFired();
    }

    #[Test]
    public function it_does_not_dispatch_to_the_queue_conditionally(): void
    {
        Archive::fake();
        $order = Order::factory()->make();

        Archive::make($order)->dispatchIf(false);

        Archive::assertNotFired();
    }

    #[Test]
    public function it_can_be_dispatched_to_the_queue_unless(): void
    {
        Archive::fake();
        $order = Order::factory()->make();

        Archive::make($order)->dispatchUnless(false);

        Archive::assertFired();
    }

    #[Test]
    public function it_does_not_dispatch_to_the_queue_unless(): void
    {
        Archive::fake();
        $order = Order::factory()->make();

        Archive::make($order)->dispatchUnless(true);

        Archive::assertNotFired();
    }

    #[Test]
    public function you_can_confirm_dispatched_times_to_the_queue(): void
    {
        Archive::fake();
        $orders = Order::factory()->times(2)->make();

        $orders->each(fn (Order $order) => Archive::make($order)->dispatch());

        Archive::assertFiredTimes($orders->count());
    }

    #[Test]
    public function it_supports_through_while_preserving_required_middleware(): void
    {
        $order = Order::factory()->make();

        WithSucceeded::make($order)->dispatch()->through([]);

        $this->assertContains(WithSucceeded::class, Context::get('execution_log'));
    }

    #[Test]
    public function it_runs_middleware_passed_to_through(): void
    {
        $order = Order::factory()->make();

        WithSucceeded::make($order)->dispatch()->through([WritesToContext::class]);

        $this->assertContains(WritesToContext::class, Context::get('execution_log'));
    }

    #[Test]
    public function it_runs_consumer_middleware_before_succeeded(): void
    {
        $order = Order::factory()->make();

        WithSucceeded::make($order)->dispatch()->through([WritesToContext::class]);

        $this->assertSame([WritesToContext::class, WithSucceeded::class], Context::get('execution_log'));
    }

    #[Test]
    public function it_calls_succeeded_after_dispatched_action_completes(): void
    {
        $order = Order::factory()->make();

        WithSucceeded::make($order)->dispatch();

        $this->assertContains(WithSucceeded::class, Context::get('execution_log'));
    }

    #[Test]
    public function it_does_not_call_succeeded_when_dispatched_action_fails(): void
    {
        try {
            WithFailedAndSucceeded::make()->dispatch();
        } catch (\RuntimeException) {
            // expected
        }

        $this->assertNotContains(WithFailedAndSucceeded::class.'::succeeded', Context::get('execution_log', []));
    }

    #[Test]
    public function it_dispatches_without_error_when_action_has_no_lifecycle_hooks(): void
    {
        $order = Order::factory()->make();

        Archive::make($order)->dispatch();

        $this->assertNull(Context::get('execution_log'));
    }

    #[Test]
    public function it_does_not_run_succeeded_when_dispatch_faked(): void
    {
        WithSucceeded::fake();

        WithSucceeded::make(Order::factory()->make())->dispatch();

        $this->assertEmpty(Context::get('execution_log', []));
    }

    #[Test]
    public function it_does_not_run_failed_when_dispatch_faked(): void
    {
        WithSucceeded::fake();

        WithSucceeded::make(Order::factory()->make())->dispatch();

        $this->assertEmpty(Context::get('execution_log', []));
    }
}
