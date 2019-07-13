<?php

namespace Flood\Tenant;

use App\User;

class Tenant
{
    public function __construct($config, $db)
    {
        $this->config = $config;
        $this->database = $db;
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function connectToUser(User $user)
    {
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
}
