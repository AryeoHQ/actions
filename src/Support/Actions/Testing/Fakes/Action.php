<?php

declare(strict_types=1);

namespace Support\Actions\Testing\Fakes;

final class Action
{
    /** @var class-string<\Support\Actions\Contracts\Action> */
    public readonly string $faked;

    public protected(set) mixed $returns = null;

    /**
     * @param  class-string<\Support\Actions\Contracts\Action>  $faked
     */
    public function __construct(string $faked)
    {
        $this->faked = $faked;
    }

    /**
     * @param  class-string<\Support\Actions\Contracts\Action>  $faked
     */
    public static function make(string $faked): self
    {
        return tap(new self($faked), function (self $action) {
            Manager::register($action);
        });
    }

    public function andReturn(mixed $value): self
    {
        $this->returns = $value;

        return $this;
    }
}
