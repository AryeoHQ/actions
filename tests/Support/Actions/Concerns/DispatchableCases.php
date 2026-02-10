<?php

declare(strict_types=1);

namespace Tests\Support\Actions\Concerns;

use Illuminate\Foundation\Bus\PendingDispatch;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Support\Orders\Actions\Archive;
use Tests\Fixtures\Support\Orders\Order;

trait DispatchableCases
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
}
