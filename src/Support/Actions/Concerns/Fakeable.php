<?php

declare(strict_types=1);

namespace Support\Actions\Concerns;

use Support\Actions\Testing\Fakes\Action;

trait Fakeable
{
    public static function fake(mixed $returns = null): void
    {
        Action::make(static::class, $returns);
    }
}
