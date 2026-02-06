<?php

declare(strict_types=1);

namespace Support\Actions\Concerns;

use Illuminate\Foundation\Queue\Queueable;

trait AsAction
{
    use Dispatchable;
    use Fakeable;
    use Nowable;
    use Queueable;

    public static function make(mixed ...$arguments): static
    {
        return new static(...$arguments); // @phpstan-ignore-line
    }
}
