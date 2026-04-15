<?php

declare(strict_types=1);

namespace Support\Actions\Providers;

use Illuminate\Bus\Dispatcher as BusDispatcher;
use Illuminate\Support\ServiceProvider;
use Support\Actions\Bus\Dispatcher;
use Support\Actions\Commands\MakeAction;

final class Provider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->extend(BusDispatcher::class, fn (BusDispatcher $dispatcher, $app) => new Dispatcher($dispatcher));

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeAction::class,
            ]);
        }
    }

    public function boot(): void
    {
        $this->loadViewsFrom(
            __DIR__.'/../../../../resources/views/rector/rules',
            'tooling.actions.rector.rules.samples'
        );
    }
}
