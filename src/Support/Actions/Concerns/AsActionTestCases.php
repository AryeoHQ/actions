<?php

declare(strict_types=1);

namespace Support\Actions\Concerns;

use Illuminate\Queue\SerializesModels;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Support\Orders\Actions\Notify;

/** @mixin \Tests\TestCase */
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

    #[Test]
    public function it_does_not_use_serializes_models(): void
    {
        $traits = class_uses_recursive(AsAction::class);

        $this->assertNotContains(
            SerializesModels::class,
            $traits,
            'AsAction must not include SerializesModels. This is a critical design decision to preserve model state at dispatch time. See README.md for details.',
        );
    }
}
