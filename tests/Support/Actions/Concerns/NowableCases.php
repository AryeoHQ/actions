<?php

declare(strict_types=1);

namespace Tests\Support\Actions\Concerns;

use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Orders\Actions\Ship;

trait NowableCases
{
    #[Test]
    public function it_can_be_executed_synchronously(): void
    {
        $result = Ship::make('test')->now();

        $this->assertEquals('test charged', $result);
    }

    #[Test]
    public function it_can_be_faked_when_executed_synchronously(): void
    {
        Ship::fake('mocked-value');

        $result = Ship::make('test')->now();

        $this->assertEquals('mocked-value', $result);
        Bus::assertDispatched(Ship::class);
    }

    #[Test]
    public function it_can_be_faked_multiple_times_when_executed_synchronously(): void
    {
        Ship::fake('first');
        $result1 = Ship::make('test')->now();

        Ship::fake('second');
        $result2 = Ship::make('test')->now();

        $this->assertEquals('first', $result1);
        $this->assertEquals('second', $result2);
        Bus::assertDispatched(Ship::class, 2);
    }

    #[Test]
    public function it_can_be_conditionally_executed_synchronously(): void
    {
        $result = Ship::make('test')->nowIf(true);

        $this->assertEquals('test charged', $result);
    }

    #[Test]
    public function it_can_be_faked_when_conditionally_executed_synchronously(): void
    {
        Ship::fake('mocked-value');

        $result = Ship::make('test')->nowIf(true);

        $this->assertEquals('mocked-value', $result);
        Bus::assertDispatched(Ship::class);
    }

    #[Test]
    public function it_does_not_execute_synchronously_conditionally(): void
    {
        $result = Ship::make('test')->nowIf(false);

        $this->assertNull($result);
    }

    #[Test]
    public function it_can_be_executed_synchronously_unless(): void
    {
        $result = Ship::make('test')->nowUnless(false);

        $this->assertEquals('test charged', $result);
    }

    #[Test]
    public function it_can_be_faked_when_executed_synchronously_unless(): void
    {
        Ship::fake('mocked-value');

        $result = Ship::make('test')->nowUnless(false);

        $this->assertEquals('mocked-value', $result);
        Bus::assertDispatched(Ship::class);
    }

    #[Test]
    public function it_does_not_execute_synchronously_unless(): void
    {
        $result = Ship::make('test')->nowUnless(true);

        $this->assertNull($result);
    }
}
