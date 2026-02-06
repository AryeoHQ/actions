<?php

declare(strict_types=1);

namespace Tests\Fixtures\Variations;

use Support\Actions\Contracts\Action;

final class MissingAsActionTraitAction implements Action
{
    public static function make(mixed ...$arguments): static
    {
        return new self;
    }

    public function handle(): void
    {
        // Implementation
    }
}
