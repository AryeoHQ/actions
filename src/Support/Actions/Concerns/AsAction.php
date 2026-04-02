<?php

declare(strict_types=1);

namespace Support\Actions\Concerns;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

trait AsAction
{
    use Dispatchable;
    use Fakeable;
    use InteractsWithQueue;
    use Nowable;
    use Queueable;

    public static function make(mixed ...$arguments): static
    {
        return new static(...$arguments); // @phpstan-ignore-line
    }
}
