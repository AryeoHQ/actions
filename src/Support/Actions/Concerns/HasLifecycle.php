<?php

declare(strict_types=1);

namespace Support\Actions\Concerns;

trait HasLifecycle
{
    public bool $dispatchesAfterSucceeded = false;

    public bool $dispatchesAfterFailed = false;

    public function dispatchAfterSucceeded(): static
    {
        $this->dispatchesAfterSucceeded = true;

        return $this;
    }

    public function dispatchAfterFailed(): static
    {
        $this->dispatchesAfterFailed = true;

        return $this;
    }

    public function clearDispatchAfter(): static
    {
        $this->dispatchesAfterSucceeded = false;
        $this->dispatchesAfterFailed = false;

        return $this;
    }

    public function initialize(): static
    {
        $this->clearJob();

        when(
            method_exists($this, 'prepare'), // @phpstan-ignore function.impossibleType, function.alreadyNarrowedType
            fn () => call_user_func([$this, 'prepare']) // @phpstan-ignore argument.type
        );

        return $this;
    }
}
