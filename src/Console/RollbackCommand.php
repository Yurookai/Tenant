<?php

namespace Flood\Tenant\Console;

use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\Console\Migrations\BaseCommand;
use Illuminate\Database\Migrations\Migrator;

class RollbackCommand extends BaseCommand
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flood:rollback {--database= : The database connection to use.}
                {--pretend : Dump the SQL queries that would be run.}
                {--seed : Indicates if the seed task should be re-run.}
                {--step : Force the migrations to be run so they can be rolled back individually.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback the last database migration';

    /**
     * The migrator instance.
     *
     * @var \Illuminate\Database\Migrations\Migrator
     */
    protected $migrator;

    /**
     * Create a new migration command instance.
     *
     * @param  \Illuminate\Database\Migrations\Migrator  $migrator
     */
    public function __construct(Migrator $migrator)
    {
        parent::__construct();

        $this->migrator = $migrator;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (!$this->confirmToProceed()) {
            return;
        }

        // Migrate or install the general database.
        $this->call(
            'migrate', ['--database' => 'general']
        );

        // Get the users from the base tenant database. For each user that has
        // been found, migrate.
        $users = $this->laravel->make(
            $this->laravel['config']['auth.providers.users.model']
        )->whereNotNull('tenant')->get();

        foreach ($users as $user) {
            $this->laravel['config']['database.connections.mysql.database'] = $user->tenant;

            $this->output->writeln(sprintf('<info>Rollbacking %s:</info> ', $user->name));

            $this->prepareDatabase('mysql');

            // Next, we will check to see if a path option has been defined. If it has
            // we will use the path relative to the root of this installation folder
            // so that migrations may be run for any path within the applications.
            $this->migrator->rollback([$this->laravel->databasePath() . '/migrations/tenant'], [
                'pretend' => $this->option('pretend'),
                'step' => $this->option('step'),
            ]);
        }
    }

    /**
     * Prepare the migration database for running.
     *
     * @param $database
     *
     * @return void
     */
    protected function prepareDatabase($database = null)
    {
        $this->migrator->setConnection($database);

        ($connection = $this->migrator->getRepository()->getConnection())->setDatabaseName(
            $this->laravel['config']['database.connections.mysql.database']
        );

        $connection->reconnect('mysql');
    }
}
