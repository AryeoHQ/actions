<?php

declare(strict_types=1);

namespace Support\Actions\Contracts;

use Illuminate\Contracts\Queue\ShouldQueue;

interface Action extends ShouldQueue
{
    public static function make(mixed ...$arguments): static;
}
