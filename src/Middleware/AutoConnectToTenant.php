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
            // Set the database.
            $this->config['database.connections.mysql.database'] = $user->tenant;

            // Reconnect to the set database.
            $this->database->reconnect('mysql');
        }

        return $next($request);
    }
}
