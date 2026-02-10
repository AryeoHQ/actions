<?php

declare(strict_types=1);

namespace Tests\Support\Actions\Concerns;

use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Support\Orders\Actions\Notify;

trait AsActionCases
{
    use DispatchableCases;
    use NowableCases;

    #[Test]
    public function it_is_makeable(): void
    {
        $action = Notify::make();

        $this->assertInstanceOf(Notify::class, $action);
    }
}
