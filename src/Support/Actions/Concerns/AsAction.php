<?php

declare(strict_types=1);

namespace Support\Actions\Concerns;

use ReflectionClass;

trait AsAction
{
    use Dispatchable;
    use Fakeable;
    use HasLifecycle;
    use InteractsWithJob;
    use Nowable;

    public static function make(mixed ...$arguments): static
    {
        return new static(...$arguments); // @phpstan-ignore-line
    }

    /**
     * @param  class-string  $attribute
     */
    public function declares(string $attribute): bool
    {
        return (new ReflectionClass($this))->getAttributes($attribute) !== [];
    }
}
