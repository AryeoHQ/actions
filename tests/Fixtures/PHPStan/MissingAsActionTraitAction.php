<?php

namespace Tests\Fixtures\PHPStan;

use Support\Actions\Contracts\Action;

final class MissingAsActionTraitAction implements Action
{
    public static function make(): static
    {
        return new static();
    }

    public function execute(): void
    {
        // Implementation
    }
}
