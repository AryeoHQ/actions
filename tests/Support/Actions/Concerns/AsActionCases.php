<?php

declare(strict_types=1);

namespace Tests\Support\Actions\Concerns;

use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Orders\Actions\Ship;

trait AsActionCases
{
    use DispatchableCases;
    use NowableCases;

    #[Test]
    public function it_is_makeable(): void
    {
        $action = Ship::make('test');

        $this->assertInstanceOf(Ship::class, $action);
    }

    #[Test]
    public function it_can_assert_action_was_not_dispatched(): void
    {
        Ship::fake();

        Bus::assertNotDispatched(Ship::class);
    }
}
