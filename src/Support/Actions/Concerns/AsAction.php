<?php

declare(strict_types=1);

namespace Support\Actions\Concerns;

use Illuminate\Support\Fluent;
use Mockery;
use Mockery\ExpectationInterface;
use Mockery\HigherOrderMessage;
use Mockery\MockInterface;

trait AsAction
{
    public static function make(): static
    {
        /** @var static */
        return app(static::class);
    }

    public static function mock(): MockInterface
    {
        return tap(
            Mockery::getContainer()->mock(static::class),
            fn ($instance) => app()->instance(static::class, $instance)
        );
    }

    /**
     * Execute the action if the condition is true.
     */
    public function executeIf(bool $shouldExecute, mixed ...$arguments): mixed
    {
        if ($shouldExecute) {
            /**
             * The ignore is here to allow for void to be a return type
             *
             * @phpstan-ignore-next-line
             */
            return $this->execute(...$arguments);
        }

        return new Fluent;
    }

    public static function shouldExecute(): ExpectationInterface|HigherOrderMessage
    {
        return static::mock()->makePartial()->shouldReceive('execute');
    }
}
