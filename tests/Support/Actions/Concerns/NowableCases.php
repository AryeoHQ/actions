<?php

declare(strict_types=1);

namespace Tests\Support\Actions\Concerns;

use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Orders\Actions\Archive;

trait NowableCases
{
    #[Test]
    public function it_can_be_executed_synchronously(): void
    {
        $result = Archive::make('test')->now();

        $this->assertEquals('test archived', $result);
    }

    #[Test]
    public function it_can_be_faked_when_executed_synchronously(): void
    {
        Archive::fake('mocked-value');

        $result = Archive::make('test')->now();

        $this->assertEquals('mocked-value', $result);
        Bus::assertDispatched(Archive::class);
    }

    #[Test]
    public function it_can_be_faked_multiple_times_when_executed_synchronously(): void
    {
        Archive::fake('first');
        $result1 = Archive::make('test')->now();

        Archive::fake('second');
        $result2 = Archive::make('test')->now();

        $this->assertEquals('first', $result1);
        $this->assertEquals('second', $result2);
        Bus::assertDispatched(Archive::class, 2);
    }

    #[Test]
    public function it_can_be_conditionally_executed_synchronously(): void
    {
        $result = Archive::make('test')->nowIf(true);

        $this->assertEquals('test archived', $result);
    }

    #[Test]
    public function it_can_be_faked_when_conditionally_executed_synchronously(): void
    {
        Archive::fake('mocked-value');

        $result = Archive::make('test')->nowIf(true);

        $this->assertEquals('mocked-value', $result);
        Bus::assertDispatched(Archive::class);
    }

    #[Test]
    public function it_does_not_execute_synchronously_conditionally(): void
    {
        $result = Archive::make('test')->nowIf(false);

        $this->assertNull($result);
    }

    #[Test]
    public function it_can_be_executed_synchronously_unless(): void
    {
        $result = Archive::make('test')->nowUnless(false);

        $this->assertEquals('test archived', $result);
    }

    #[Test]
    public function it_can_be_faked_when_executed_synchronously_unless(): void
    {
        Archive::fake('mocked-value');

        $result = Archive::make('test')->nowUnless(false);

        $this->assertEquals('mocked-value', $result);
        Bus::assertDispatched(Archive::class);
    }

    #[Test]
    public function it_does_not_execute_synchronously_unless(): void
    {
        $result = Archive::make('test')->nowUnless(true);

        $this->assertNull($result);
    }
}
