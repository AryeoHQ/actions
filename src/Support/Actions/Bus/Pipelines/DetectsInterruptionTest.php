<?php

declare(strict_types=1);

namespace Support\Actions\Bus\Pipelines;

use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use stdClass;
use Support\Actions\Bus\Pipelines\Exceptions\Interrupted;
use Tests\Fixtures\Support\Orders\Middleware\Blocks;
use Tests\TestCase;

#[CoversClass(DetectsInterruption::class)]
class DetectsInterruptionTest extends TestCase
{
    #[Test]
    public function it_returns_the_result_when_all_pipes_pass_control(): void
    {
        $result = (new DetectsInterruption(app()))
            ->send(new stdClass)
            ->through([
                fn ($passable, $next) => $next($passable),
            ])
            ->then(fn () => 'completed');

        $this->assertSame('completed', $result);
    }

    #[Test]
    public function it_throws_interrupted_when_a_pipe_does_not_pass_control(): void
    {
        $blocker = new class
        {
            public function handle(object $passable, callable $next): void {}
        };

        $this->expectException(Interrupted::class);

        (new DetectsInterruption(app()))
            ->send(new stdClass)
            ->through([$blocker])
            ->then(fn () => 'completed');
    }

    #[Test]
    public function it_attributes_the_interruption_to_the_middleware_that_did_not_pass_control(): void
    {
        $passThrough = new class
        {
            public function handle(object $passable, callable $next): mixed
            {
                return $next($passable);
            }
        };

        $blocker = new class
        {
            public function handle(object $passable, callable $next): void {}
        };

        $this->expectException(Interrupted::class);

        try {
            (new DetectsInterruption(app()))
                ->send(new stdClass)
                ->through([$passThrough, $blocker])
                ->then(fn () => 'completed');
        } catch (Interrupted $exception) {
            $this->assertSame($blocker::class, $exception->middleware);

            throw $exception;
        }
    }

    #[Test]
    public function it_does_not_misfire_when_the_destination_throws(): void
    {
        $this->expectException(LogicException::class);

        (new DetectsInterruption(app()))
            ->send(new stdClass)
            ->through([
                fn ($passable, $next) => $next($passable),
            ])
            ->then(function () {
                throw new LogicException;
            });
    }

    #[Test]
    public function it_does_not_misfire_when_a_pipe_throws(): void
    {
        $this->expectException(LogicException::class);

        (new DetectsInterruption(app()))
            ->send(new stdClass)
            ->through([
                fn () => throw new LogicException,
            ])
            ->then(fn () => 'completed');
    }

    #[Test]
    public function it_attributes_a_string_pipe_by_class_name(): void
    {
        $this->expectException(Interrupted::class);

        (new DetectsInterruption(app()))
            ->send(new stdClass)
            ->through([Blocks::class])
            ->then(fn () => 'completed');
    }

    #[Test]
    public function it_allows_a_pipe_to_recover_from_a_downstream_exception(): void
    {
        $recover = function ($passable, $next) {
            try {
                return $next($passable);
            } catch (LogicException) {
                return 'fallback';
            }
        };

        $result = (new DetectsInterruption(app()))
            ->send(new stdClass)
            ->through([$recover])
            ->then(fn () => throw new LogicException);

        $this->assertSame('fallback', $result);
    }
}
