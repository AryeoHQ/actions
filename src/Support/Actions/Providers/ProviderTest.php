<?php

declare(strict_types=1);

namespace Support\Actions\Providers;

use Illuminate\Contracts\Bus\Dispatcher as BusDispatcherContract;
use Illuminate\Contracts\Bus\QueueingDispatcher as QueueingDispatcherContract;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Actions\Bus\Dispatcher;
use Tests\TestCase;

#[CoversClass(Provider::class)]
class ProviderTest extends TestCase
{
    #[Test]
    public function it_registers_commands_when_running_in_console(): void
    {
        $this->assertTrue(
            collect(Artisan::all())->has('make:action')
        );
    }

    #[Test]
    public function it_decorates_the_bus_dispatcher(): void
    {
        $this->assertInstanceOf(
            Dispatcher::class,
            $this->app->make(\Illuminate\Bus\Dispatcher::class)
        );

        $this->assertInstanceOf(
            Dispatcher::class,
            $this->app->make(BusDispatcherContract::class)
        );

        $this->assertInstanceOf(
            Dispatcher::class,
            $this->app->make(QueueingDispatcherContract::class)
        );

        $this->assertSame(
            $this->app->make(\Illuminate\Bus\Dispatcher::class),
            $this->app->make(BusDispatcherContract::class)
        );

        $this->assertSame(
            $this->app->make(\Illuminate\Bus\Dispatcher::class),
            $this->app->make(QueueingDispatcherContract::class)
        );

        $this->assertInstanceOf(
            Dispatcher::class,
            \Illuminate\Support\Facades\Bus::getFacadeRoot()
        );
    }
}
