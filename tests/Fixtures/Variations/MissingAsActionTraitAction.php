<?php

declare(strict_types=1);

namespace Tests\Fixtures\Variations;

use Support\Actions\Contracts\Action;

final class MissingAsActionTraitAction implements Action
{
    public static function make(): static
    {
        return new self;
    }

    public function execute(): void
    {
        // Implementation
    }
}
