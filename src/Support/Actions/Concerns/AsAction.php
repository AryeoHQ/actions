<?php

declare(strict_types=1);

namespace Support\Actions\Concerns;

trait AsAction
{
    use Dispatchable;
    use Fakeable;
    use HasLifecycle;
    use Nowable;

    public static function make(mixed ...$arguments): static
    {
        return new static(...$arguments); // @phpstan-ignore-line
    }
}
