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
     */
    public function connect(array $config)
    {
        $options = $this->getOptions($config);

        $path = $this->parseDatabasePath($config['database']);

        $connection = $this->createConnection("sqlite:{$path}", $config, $options);

        $this->configureForeignKeyConstraints($connection, $config);
        $this->configureBusyTimeout($connection, $config);
        $this->configureJournalMode($connection, $config);
        $this->configureSynchronous($connection, $config);
        $this->configureAttachedDatabases($connection, $config);

        return $connection;
    }

    /**
     * Get the absolute database path.
     * 
     * @param  string  $path
     * @return string
     *
     * @throws \Illuminate\Database\SQLiteDatabaseDoesNotExistException
     */
    protected function parseDatabasePath(string $path): string
    {
        // SQLite supports "in-memory" databases that only last as long as the owning
        // connection does. These are useful for tests or for short lifetime store
        // querying. In-memory databases shall be anonymous (:memory:) or named.
        if ($path === ':memory:' ||
            str_contains($path, '?mode=memory') ||
            str_contains($path, '&mode=memory')
        ) {
            return $path;
        }

        $realPath = realpath($path);

        // Here we'll verify that the SQLite database exists before going any further
        // as the developer probably wants to know if the database exists and this
        // SQLite driver will not throw any exception if it does not by default.
        if ($realPath === false) {
            throw new SQLiteDatabaseDoesNotExistException($path);
        }

        return $realPath;
    }

    /**
     * Enable or disable foreign key constraints if configured.
     *
     * @param  \PDO  $connection
     * @param  array  $config
     * @return void
     */
    protected function configureForeignKeyConstraints($connection, array $config): void
    {
        if (! isset($config['foreign_key_constraints'])) {
            return;
        }

        $foreignKeys = $config['foreign_key_constraints'] ? 1 : 0;

        $connection->prepare("pragma foreign_keys = {$foreignKeys}")->execute();
    }

    /**
     * Set the busy timeout if configured.
     *
     * @param  \PDO  $connection
     * @param  array  $config
     * @return void
     */
    protected function configureBusyTimeout($connection, array $config): void
    {
        if (! isset($config['busy_timeout'])) {
            return;
        }

        $connection->prepare("pragma busy_timeout = {$config['busy_timeout']}")->execute();
    }

    /**
     * Set the journal mode if configured.
     *
     * @param  \PDO  $connection
     * @param  array  $config
     * @return void
     */
    protected function configureJournalMode($connection, array $config): void
    {
        if (! isset($config['journal_mode'])) {
            return;
        }

        $connection->prepare("pragma journal_mode = {$config['journal_mode']}")->execute();
    }

    /**
     * Set the synchronous mode if configured.
     *
     * @param  \PDO  $connection
     * @param  array  $config
     * @return void
     */
    protected function configureSynchronous($connection, array $config): void
    {
        if (! isset($config['synchronous'])) {
            return;
        }

        $connection->prepare("pragma synchronous = {$config['synchronous']}")->execute();
    }

    /**
     * Attach databases as schemas if configured.
     *
     * @param  \PDO  $connection
     * @param  array  $config
     * @return void
     */
    protected function configureAttachedDatabases($connection, array $config): void
    {
        if (! isset($config['schemas'])) {
            return;
        }

        foreach ($config['schemas'] as $schema => $path) {
            $connection->prepare("attach database '{$this->parseDatabasePath($path)}' as '{$schema}'")->execute();
        }
    }
}
