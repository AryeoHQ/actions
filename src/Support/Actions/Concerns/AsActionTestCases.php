<?php

declare(strict_types=1);

namespace Support\Actions\Concerns;

use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Support\Orders\Actions\Notify;

trait AsActionTestCases
{
    use DispatchableTestCases;
    use NowableTestCases;

    #[Test]
    public function it_is_makeable(): void
    {
        $action = Notify::make();

        $this->assertInstanceOf(Notify::class, $action);
    }
}
