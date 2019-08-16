<?php

namespace ImLiam\EnvironmentSetCommand;

use Illuminate\Support\ServiceProvider;

class EnvironmentSetCommandServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        // ...
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->app->bind('command.env:set', Environ::class);

        $this->commands([
            'command.env:set'
        ]);
    }
}
