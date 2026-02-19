<?php

declare(strict_types=1);

namespace Support\Actions\Providers;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Support\Actions\Commands\MakeAction;

final class ActionServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
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

    /**
     * @return array<string>
     */
    public function provides(): array
    {
        return [
            MakeAction::class,
        ];
    }
}
