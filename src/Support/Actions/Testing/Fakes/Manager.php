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
     * @return array<class-string, Action>
     */
    public static function faked(): array
    {
        return Context::get('job.fakes', []);
    }

    private static function track(Action $fake): void
    {
        $fakes = self::faked();
        $fakes[$fake->faked] = $fake;
        Context::add('job.fakes', $fakes);
    }

    private static function fakeOnFirstRegistration(): void
    {
        if (app()->bound('dispatcher.busfake')) {
            return;
        }

        Bus::fake(array_keys(self::faked()));

        app()->instance('dispatcher.busfake', app(Dispatcher::class));

        $mockDispatcher = Mockery::mock(Dispatcher::class);
        $mockDispatcher->shouldReceive('dispatchNow')->andReturnUsing(self::handleSyncDispatch());
        $mockDispatcher->shouldReceive('dispatchSync')->andReturnUsing(self::handleSyncDispatch());
        $mockDispatcher->shouldReceive('dispatch')->andReturnUsing(self::handleAsyncDispatch());
        $mockDispatcher->shouldReceive('dispatchToQueue')->andReturnUsing(self::handleAsyncDispatch());
        $mockDispatcher->shouldReceive('getCommandHandler')->andReturnUsing(fn ($job) => app('dispatcher.busfake')->getCommandHandler($job));

        app()->instance(Dispatcher::class, $mockDispatcher);
    }

    private static function handleSyncDispatch(): Closure
    {
        return function ($job) {
            return static::handleDispatch($job, fn ($job) => $job->handle());
        };
    }

    private static function handleAsyncDispatch(): Closure
    {
        return function ($job) {
            return static::handleDispatch($job, fn ($job) => app('dispatcher.busfake')->dispatch($job));
        };
    }

    private static function handleDispatch(object $job, Closure $fallback): mixed
    {
        $fakes = self::faked();
        $fake = $fakes[$job::class] ?? null;

        if ($fake !== null) {
            app('dispatcher.busfake')->dispatchNow($job);

            return $fake->return();
        }

        return $fallback($job);
    }
}
