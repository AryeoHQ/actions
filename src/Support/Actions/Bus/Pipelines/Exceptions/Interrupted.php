<?php

declare(strict_types=1);

namespace Support\Actions\Bus\Pipelines\Exceptions;

use RuntimeException;

final class Interrupted extends RuntimeException
{
    /**
     * @var class-string
     */
    public readonly string $action;

    /**
     * @var class-string
     */
    public readonly string $middleware;

    /**
     * @param  class-string  $action
     * @param  class-string  $middleware
     */
    private function __construct(string $action, string $middleware)
    {
        $this->action = $action;
        $this->middleware = $middleware;

        parent::__construct("The [{$action}] operation was interrupted by the [{$middleware}] middleware.");
    }

    /**
     * @param  class-string  $action
     * @param  class-string  $middleware
     */
    public static function by(string $action, string $middleware): self
    {
        return new self($action, $middleware);
    }
}
