<?php

namespace Support\Actions\Providers;

use Illuminate\Support\ServiceProvider;
use Support\Actions\Commands\MakeAction;
use Illuminate\Contracts\Support\DeferrableProvider;

class ActionServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeAction::class,
            ]);
        }
    }

    public function provides(): array
    {
        return [
            MakeAction::class,
        ];
    }
}