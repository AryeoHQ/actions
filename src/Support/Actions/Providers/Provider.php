<?php

declare(strict_types=1);

namespace Support\Actions\Providers;

use Illuminate\Support\ServiceProvider;
use Support\Actions\Bus\Dispatcher;
use Support\Actions\Commands\MakeAction;

final class Provider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->extend(
            \Illuminate\Bus\Dispatcher::class,
            fn (\Illuminate\Bus\Dispatcher $dispatcher, $app) => new Dispatcher($dispatcher)
        );

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
