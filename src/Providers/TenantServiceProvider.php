<?php

namespace Flood\Tenant\Providers;

use Flood\Tenant\Console\MigrateCommand;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class TenantServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCommands();
    }

    private function registerCommands()
    {
        $this->app->singleton(MigrateCommand::class, function (Application $app) {
            return new MigrateCommand($app->make('migrator'));
        });

        $this->commands(MigrateCommand::class);
    }
}