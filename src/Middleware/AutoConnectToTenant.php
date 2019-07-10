<?php

namespace Flood\Tenant\Middleware;

use Closure;
use Illuminate\Config\Repository;
use Illuminate\Database\DatabaseManager;

class AutoConnectToTenant
{
    /**
     * Database manager for connections.
     *
     * @var DatabaseManager
     */
    private $database;

    /**
     * Configuration for the app.
     *
     * @var Repository
     */
    private $config;

    public function __construct(DatabaseManager $databaseManager, Repository $config)
    {
        $this->database = $databaseManager;
        $this->config = $config;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($user = $request->user()) {
            $this->config['database.connections.' . ($connection = 'mysql:'.$user->id.':'.$user->tenant)] = array_merge(
                $this->config['database.connections.mysql'],
                [
                    'database' => $user->tenant,
                ]
            );

            $this->config['database.connections.mysql.database'] = $user->tenant;
            $this->config['database.default'] = $connection;

            // Reconnect to the set database.
            $this->database->setDefaultConnection($connection);
            $this->database->disconnect('mysql');
            $this->database->reconnect($connection);
        }

        return $next($request);
    }
}
