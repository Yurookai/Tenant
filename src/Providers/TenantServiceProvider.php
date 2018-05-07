<?php

namespace Flood\Tenant\Providers;

use Flood\Tenant\Console\SeedCommand;
use Illuminate\Support\ServiceProvider;
use Flood\Tenant\Console\MigrateCommand;
use Illuminate\Contracts\Foundation\Application;

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

        $this->app->singleton(SeedCommand::class, function (Application $app) {
            return new SeedCommand($app->make('db'));
        });

        $this->commands(MigrateCommand::class);
        $this->commands(SeedCommand::class);
    }
}