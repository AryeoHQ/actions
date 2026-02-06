<?php

declare(strict_types=1);

namespace Support\Actions\Contracts;

interface Action
{
    public static function make(mixed ...$arguments): static;
}
