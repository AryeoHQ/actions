<?php

declare(strict_types=1);

namespace Support\Actions\Providers;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Support\Actions\Commands\MakeAction;

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
