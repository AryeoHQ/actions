<?php

declare(strict_types=1);

namespace Support\Actions\Concerns;

use Illuminate\Contracts\Bus\Dispatcher;

trait Nowable
{
    public function now(): mixed
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
