<?php

namespace ImLiam\EnvironmentSetCommand;

use Illuminate\Support\ServiceProvider;

class EnvironmentSetCommandServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        // ...
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->app->bind('command.env:set', EnvironmentSetCommand::class);

        $this->commands([
            'command.env:set'
        ]);
    }
}
