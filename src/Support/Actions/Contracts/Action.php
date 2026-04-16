<?php

declare(strict_types=1);

namespace Support\Actions\Contracts;

use Illuminate\Contracts\Queue\ShouldQueue;

interface Action extends ShouldQueue
{
    public static function make(mixed ...$arguments): static;

    /**
     * This implementation is provided to `AsAction` by `\Illuminate\Foundation\Queue\Queueable`.
     * Since we do not own that definition we cannot have this contract define runtime types.
     *
     * @param  array<array-key, object|class-string>|object|class-string  $middleware
     * @return $this
     */
    public function through($middleware);
}
