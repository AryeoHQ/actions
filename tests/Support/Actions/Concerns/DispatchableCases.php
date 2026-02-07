<?php

declare(strict_types=1);

namespace Tests\Support\Actions\Concerns;

use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Orders\Actions\Archive;

trait DispatchableCases
{
    #[Test]
    public function it_can_be_dispatched_to_the_queue(): void
    {
        Archive::fake();

        Archive::make('test')->dispatch();

        Bus::assertDispatched(Archive::class);
    }

    #[Test]
    public function it_returns_pending_dispatch_when_dispatched(): void
    {
        $pendingDispatch = Archive::make('test')->dispatch();

        $this->assertInstanceOf(PendingDispatch::class, $pendingDispatch);
    }

    #[Test]
    public function it_can_be_faked_when_dispatched_to_the_queue(): void
    {
        Archive::fake();

        Archive::make('test')->dispatch();

        Bus::assertDispatched(Archive::class);
    }

    #[Test]
    public function it_can_be_conditionally_dispatched_to_the_queue(): void
    {
        Archive::fake();

        Archive::make('test')->dispatchIf(true);

        Bus::assertDispatched(Archive::class);
    }

    #[Test]
    public function it_does_not_dispatch_to_the_queue_conditionally(): void
    {
        Archive::fake();

        Archive::make('test')->dispatchIf(false);

        Bus::assertNotDispatched(Archive::class);
    }

    #[Test]
    public function it_can_be_dispatched_to_the_queue_unless(): void
    {
        Archive::fake();

        Archive::make('test')->dispatchUnless(false);

        Bus::assertDispatched(Archive::class);
    }

    #[Test]
    public function it_does_not_dispatch_to_the_queue_unless(): void
    {
        Archive::fake();

        Archive::make('test')->dispatchUnless(true);

        Bus::assertNotDispatched(Archive::class);
    }
}
