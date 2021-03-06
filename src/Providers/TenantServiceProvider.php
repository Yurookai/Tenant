<?php

namespace Flood\Tenant\Providers;

use Flood\Tenant\Console\SeedCommand;
use Illuminate\Support\ServiceProvider;
use Flood\Tenant\Console\MigrateCommand;
use Flood\Tenant\Console\RollbackCommand;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Contracts\Foundation\Application;
use Flood\Tenant\Console\RunCommand;
use Flood\Tenant\Tenant;

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

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app['queue']->createPayloadUsing(function (string $connection, string $queue = null, array $payload = []) {
            return [
                'database' => $this->app['db']->getDefaultConnection(),
            ];
        });

        $this->app['events']->listen(JobProcessing::class, function ($event) {
            $identifier = array_get($event->job->payload(), 'database');

            if ($identifier) {
                $data = explode(':', $identifier);

                $this->app['config']['database.connections.' . $identifier] = array_merge(
                    $this->app['config']['database.connections.mysql'],
                    [
                        'database' => $database = ($data[2] ?? $data[0] ?? 'mysql'),
                    ]
                );

                $this->app['config']['database.connections.mysql.database'] = $database;
                $this->app['config']['database.default'] = $identifier;

                $this->app['db']->setDefaultConnection($identifier);
                $this->app['db']->disconnect('mysql');
                $this->app['db']->reconnect($identifier);
            }
        });
    }

    private function registerCommands()
    {
        $this->app->singleton(MigrateCommand::class, function (Application $app) {
            return new MigrateCommand($app->make('migrator'));
        });

        $this->app->singleton(RollbackCommand::class, function (Application $app) {
            return new RollbackCommand($app->make('migrator'));
        });

        $this->app->singleton(SeedCommand::class, function (Application $app) {
            return new SeedCommand($app->make('db'));
        });

        $this->app->singleton(RunCommand::class, function () {
            return new RunCommand();
        });

        $this->app->singleton(Tenant::class, function (Application $app) {
            return new Tenant($app->make('config'), $app->make('db'));
        });

        $this->commands(MigrateCommand::class);
        $this->commands(RollbackCommand::class);
        $this->commands(SeedCommand::class);
        $this->commands(RunCommand::class);
    }
}
