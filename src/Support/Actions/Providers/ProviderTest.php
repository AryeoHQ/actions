<?php

declare(strict_types=1);

namespace Support\Actions\Providers;

use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
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
        $this->assertInstanceOf(Dispatcher::class, $this->app->make(BusDispatcher::class));
    }
}
