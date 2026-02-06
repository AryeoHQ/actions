<?php

declare(strict_types=1);

namespace Support\Actions\Concerns;

use Illuminate\Foundation\Bus\PendingDispatch;

/**
 * @method PendingDispatch dispatch()
 */
trait Dispatchable
{
    public function dispatch(): PendingDispatch
    {
        return new PendingDispatch($this);
    }

    public function dispatchIf(bool $condition): null|PendingDispatch
    {
        return match ($condition) {
            true => $this->dispatch(),
            false => null,
        };
    }

    public function dispatchUnless(bool $condition): null|PendingDispatch
    {
        return $this->dispatchIf(! $condition);
    }
}
