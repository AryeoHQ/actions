<?php

declare(strict_types=1);

namespace Support\Actions\Bus\Exceptions;

use RuntimeException;

final class Prevented extends RuntimeException
{
    /**
     * @var class-string
     */
    public readonly string $action;

    /**
     * @param  class-string  $action
     */
    private function __construct(string $action)
    {
        $this->action = $action;

        parent::__construct('The ['.class_basename($action).'] operation was prevented because another copy holds the unique lock.');
    }

    /**
     * @param  class-string  $action
     */
    public static function for(string $action): self
    {
        return new self($action);
    }
}
