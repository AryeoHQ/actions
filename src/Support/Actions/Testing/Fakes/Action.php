<?php

declare(strict_types=1);

namespace Support\Actions\Testing\Fakes;

final class Action
{
    /** @var class-string */
    public readonly string $faked;

    public readonly mixed $returns;

    public function __construct(string $faked, mixed $returns = null)
    {
        $this->faked = $faked;
        $this->returns = $returns;
    }

    public static function make(string $fake, mixed $returns = null): void
    {
        Manager::register(
            new self($fake, $returns)
        );
    }

    public function return(): mixed
    {
        return match ($this->returns instanceof \Closure) {
            true => ($this->returns)(),
            false => $this->returns,
        };
    }
}
