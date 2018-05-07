<?php

namespace Flood\Tenant\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Database\ConnectionResolverInterface as Resolver;

class SeedCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name of the console command.
     *
     * @var string
     */
    protected $name = 'flood:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the database seeders';

    /**
     * The connection resolver instance.
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * Create a new migration command instance.
     *
     * @param \Illuminate\Database\ConnectionResolverInterface  $resolver
     */
    public function __construct(Resolver $resolver)
    {
        parent::__construct();

        $this->resolver = $resolver;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        // Get the users from the base tenant database. For each user that has
        // been found, migrate.
        $users = $this->laravel->make(
            $this->laravel['config']['auth.providers.users.model']
        )->whereNotNull('tenant')->get();

        foreach ($users as $user) {
            $this->laravel['config']['database.connections.mysql.database'] = $user->tenant;

            $this->output->writeln(sprintf('<info>Seeding %s:</info> ', $user->name));

            $this->prepareDatabase('mysql');

            Model::unguarded(function () {
                $this->getSeeder()->__invoke();
            });
        }
    }
    
    /**
     * Prepare the migration database for seeding.
     *
     * @param $database
     *
     * @return void
     */
    protected function prepareDatabase($database = null)
    {
        $this->resolver->setDefaultConnection($database);

        ($connection = $this->resolver)->setDatabaseName(
            $this->laravel['config']['database.connections.mysql.database']
        );

        $connection->reconnect('mysql');
    }

    /**
     * Get a seeder instance from the container.
     *
     * @return \Illuminate\Database\Seeder
     */
    protected function getSeeder()
    {
        $class = $this->laravel->make($this->input->getOption('class'));

        return $class->setContainer($this->laravel)->setCommand($this);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['class', null, InputOption::VALUE_OPTIONAL, 'The class name of the root seeder', 'DatabaseSeeder'],

            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'],
        ];
    }
}
