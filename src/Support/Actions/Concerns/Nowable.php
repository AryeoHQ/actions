<?php

declare(strict_types=1);

namespace Support\Actions\Concerns;

use Illuminate\Contracts\Bus\Dispatcher;
use Throwable;

trait Nowable
{
    public function now(): mixed
    {
        try {
            $result = $this->dispatchNow();
        } catch (Throwable $throwable) {
            when(
                method_exists($this, 'failed'),
                fn () => rescue(fn () => call_user_func([$this, 'failed'], $throwable), report: true)
            );

            throw $throwable;
        }

        when(
            method_exists($this, 'succeeded'),
            fn () => rescue(fn () => call_user_func([$this, 'succeeded']), report: true)
        );

        return $result;
    }

    protected function dispatchNow(): mixed
    {
        return app(Dispatcher::class)->dispatchNow($this);
    }

    public function nowIf(bool $condition): mixed
    {
        return match ($condition) {
            true => $this->now(),
            false => null,
        };
    }

    public function nowUnless(bool $condition): mixed
    {
        return $this->nowIf(! $condition);
    }
}
