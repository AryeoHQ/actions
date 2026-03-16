<?php

declare(strict_types=1);

namespace Support\Actions\Providers;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Actions\Commands\MakeAction;
use Tests\TestCase;

#[CoversClass(Provider::class)]
class ProviderTest extends TestCase
{
    #[Test]
    public function it_implements_deferrable_provider(): void
    {
        $provider = new Provider($this->app);

        $this->assertInstanceOf(DeferrableProvider::class, $provider);
    }

    #[Test]
    public function it_provides_make_action_command(): void
    {
        $provider = new Provider($this->app);

        $provides = $provider->provides();

        $this->assertCount(1, $provides);
        $this->assertContains(MakeAction::class, $provides);
    }

    #[Test]
    public function it_registers_commands_when_running_in_console(): void
    {
        $this->assertTrue(
            collect(Artisan::all())->has('make:action')
        );
    }
}
