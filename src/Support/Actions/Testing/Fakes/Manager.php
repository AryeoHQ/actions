<?php

declare(strict_types=1);

namespace Support\Actions\Testing\Fakes;

use Closure;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Context;
use Mockery;

final class Manager
{
    public static function register(Action $fake): void
    {
        self::track($fake);
        self::fakeOnFirstRegistration();
    }

    /**
     * @return array<class-string, \Support\Actions\Testing\Fakes\Action>
     */
    public static function fakes(): array
    {
        return Context::get(self::class, []);
    }

    public static function isFaked(object $job): bool
    {
        return array_key_exists($job::class, self::fakes());
    }

    private static function fakeFor(object $job): Action
    {
        return self::fakes()[$job::class];
    }

    private static function track(Action $fake): void
    {
        $fakes = self::fakes();
        $fakes[$fake->faked] = $fake;
        Context::forget(self::class);
        Context::add(self::class, $fakes);
    }

    private static function fakeOnFirstRegistration(): void
    {
        if (app()->bound('dispatcher.busfake')) {
            $busFake = app('dispatcher.busfake');

            if ($busFake instanceof \Illuminate\Support\Testing\Fakes\BusFake) {
                $reflection = new \ReflectionClass($busFake);
                $property = $reflection->getProperty('jobsToFake');
                $property->setValue($busFake, array_keys(self::fakes()));
            }

            return;
        }

        Bus::fake(array_keys(self::fakes()));

        app()->instance('dispatcher.busfake', app(Dispatcher::class));

        $mockDispatcher = Mockery::mock(Dispatcher::class);
        $mockDispatcher->shouldReceive('dispatchNow')->andReturnUsing(self::handle('dispatchNow'));
        $mockDispatcher->shouldReceive('dispatch')->andReturnUsing(self::handle('dispatch'));
        $mockDispatcher->shouldReceive('dispatchToQueue')->andReturnUsing(self::handle('dispatch'));
        $mockDispatcher->shouldReceive('getCommandHandler')->andReturnUsing(fn ($job) => app('dispatcher.busfake')->getCommandHandler($job));

        app()->instance(Dispatcher::class, $mockDispatcher);
    }

    private static function handle(string $method): Closure
    {
        return function ($job) use ($method) {
            if (self::isFaked($job)) {
                return self::executeFakedJob($job, $method);
            }

            return app('dispatcher.busfake')->{$method}($job);
        };
    }

    private static function executeFakedJob(object $job, string $method): mixed
    {
        $fake = self::fakeFor($job);

        app('dispatcher.busfake')->{$method}($job);

        return match ($fake->returns instanceof Closure) {
            true => ($fake->returns)($job),
            false => $fake->returns,
        };
    }
}
