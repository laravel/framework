<?php

namespace Illuminate\Database\Connectors;

use Illuminate\Database\SQLiteDatabaseDoesNotExistException;

class SQLiteConnector extends Connector implements ConnectorInterface
{
    /**
     * Establish a database connection.
     *
     * @param  array  $config
     * @return \PDO
     *
     * @throws \Illuminate\Database\SQLiteDatabaseDoesNotExistException
     */
    public function connect(array $config)
    {
        $options = $this->getOptions($config);

        // SQLite supports "in-memory" databases that only last as long as the owning
        // connection does. These are useful for tests or for short lifetime store
        // querying. In-memory databases shall be anonymous (:memory:) or named.
        if ($config['database'] === ':memory:' ||
            str_contains($config['database'], '?mode=memory') ||
            str_contains($config['database'], '&mode=memory')
        ) {
            return $this->createConnection('sqlite:'.$config['database'], $config, $options);
        }

        $path = realpath($config['database']) ?: realpath(base_path($config['database']));

        // Here we'll verify that the SQLite database exists before going any further
        // as the developer probably wants to know if the database exists and this
        // SQLite driver will not throw any exception if it does not by default.
        if ($path === false) {
            throw new SQLiteDatabaseDoesNotExistException($config['database']);
        }

        return $this->createConnection("sqlite:{$path}", $config, $options);
    }
}
