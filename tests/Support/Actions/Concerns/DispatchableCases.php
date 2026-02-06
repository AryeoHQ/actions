<?php

declare(strict_types=1);

namespace Tests\Support\Actions\Concerns;

use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Orders\Actions\Ship;

trait DispatchableCases
{
    #[Test]
    public function it_can_be_dispatched_to_the_queue(): void
    {
        Ship::fake();

        Ship::make('test')->dispatch();

        Bus::assertDispatched(Ship::class);
    }

    #[Test]
    public function it_returns_pending_dispatch_when_dispatched(): void
    {
        $pendingDispatch = Ship::make('test')->dispatch();

        $this->assertInstanceOf(PendingDispatch::class, $pendingDispatch);
    }

    #[Test]
    public function it_can_be_faked_when_dispatched_to_the_queue(): void
    {
        Ship::fake();

        Ship::make('test')->dispatch();

        Bus::assertDispatched(Ship::class);
    }

    #[Test]
    public function it_can_be_conditionally_dispatched_to_the_queue(): void
    {
        Ship::fake();

        Ship::make('test')->dispatchIf(true);

        Bus::assertDispatched(Ship::class);
    }

    #[Test]
    public function it_does_not_dispatch_to_the_queue_conditionally(): void
    {
        Ship::fake();

        Ship::make('test')->dispatchIf(false);

        Bus::assertNotDispatched(Ship::class);
    }

    #[Test]
    public function it_can_be_dispatched_to_the_queue_unless(): void
    {
        Ship::fake();

        Ship::make('test')->dispatchUnless(false);

        Bus::assertDispatched(Ship::class);
    }

    #[Test]
    public function it_does_not_dispatch_to_the_queue_unless(): void
    {
        Ship::fake();

        Ship::make('test')->dispatchUnless(true);

        Bus::assertNotDispatched(Ship::class);
    }
}
