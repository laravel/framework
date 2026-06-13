<?php

namespace Illuminate\Database\Console\Concerns;

use Illuminate\Support\Str;

trait ResolvesDirectConnection
{
    /**
     * Resolve a database connection, preferring the direct variant when configured.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $connections
     * @param  string|null  $database
     * @return \Illuminate\Database\Connection
     */
    protected function resolveConnection($connections, $database)
    {
        $name = $database ?: $connections->getDefaultConnection();
        $connection = $connections->connection($name);

        return $connection->usesDirectConnection() && ! Str::endsWith($name, ['::read', '::write', '::direct'])
            ? $connections->connection($name.'::direct')
            : $connection;
    }
}
