<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling;

use Support\Actions\Contracts\Action;

#[\AllowDynamicProperties]
final class MissingAsActionTraitActionWithAttribute implements Action
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
