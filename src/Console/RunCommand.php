<?php

namespace Flood\Tenant\Console;

use Illuminate\Console\Command;

class RunCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flood:run {run : The artisan command to run for the tenants}
        {--tenant=* : The tenant(s) to run the command for}
        {--argument=* : Arguments to pass onto the command}
        {--option=* : Options to pass onto the command}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run commands on all tenants';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $query = \App\User::query();

        if ($ids = $this->option('tenant')) {
            $query->whereIn('id', $ids);
        }

        $options = collect($this->option('option') ?? [])
            ->mapWithKeys(function ($value, $key) {
                list($key, $value) = explode('=', $value);

                return ["--$key" => $value];
            })
            ->merge($this->option('argument') ?? [])
            ->mapWithKeys(function ($value, $key) {
                if (!Str::startsWith($key, '--')) {
                    list($key, $value) = explode('=', $value);
                }

                return [$key => $value];
            });

        $exitCodes = [];

        $query->chunk(50, function ($users) use ($options, &$exitCodes) {
            $app = $this->getLaravel();

            foreach ($users as $user) {
                $app['config']['database.connections.mysql.database'] = $user->tenant;

                $app['db']->reconnect('mysql');

                $exitCodes[] = $this->call(
                    $this->argument('run'),
                    $options->toArray()
                );
            }
        });

        if (count($exitCodes) === 0) {
            $this->warn("Command was executed on zero tenants.");
        }
    }
}
