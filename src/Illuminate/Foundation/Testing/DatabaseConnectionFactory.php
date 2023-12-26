<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Database\Connectors\ConnectionFactory as ConnectionFactoryContract;
use Illuminate\Database\Connectors\ConnectionFactory;

/**
 * @internal
 */
class DatabaseConnectionFactory extends ConnectionFactory
{
    /**
     * List of cached database connections.
     *
     * @var array<string, \Illuminate\Database\Connection>
     */
    protected static array $cachedConnections = [];

    /**
     * Create a new connection factory instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    public function __construct(
        Container $container,
        protected ConnectionFactoryContract $factory
    ) {
        parent::__construct($container);
    }

    /**
     * Establish a PDO connection based on the configuration.
     *
     * @param  array  $config
     * @param  string  $name
     * @return \Illuminate\Database\Connection
     */
    #[\Override]
    public function make(array $config, $name)
    {
        $key = $name ?? $config['name'];

        // In-Memory Databases doesn't have any max connections limitation so it should be safe to just create a new connection between tests.
        // Because some tests may be depend on thier volatile, we should always create new connections to avoid carrying over previous data.
        if ($config['driver'] === 'sqlite' && $config['database'] === ':memory:') {
            return $this->factory->make($config, $name);
        }

        if (! isset(static::$cachedConnections[$key]) || is_null(static::$cachedConnections[$key]->getRawPdo() ?? null)) {
            return static::$cachedConnections[$key] = $this->factory->make($config, $name);
        }

        $config = $this->parseConfig($config, $name);

        $connection = $this->createConnection(
            $config['driver'], static::$cachedConnections[$key]->getRawPdo(), $config['database'], $config['prefix'], $config
        )->setReadPdo(static::$cachedConnections[$key]->getRawReadPdo());

        return static::$cachedConnections[$key] = $connection;
    }

    /**
     * Flush the current state.
     *
     * @return void
     */
    public static function flushState(): void
    {
        foreach (static::$cachedConnections as $connection) {
            $connection->disconnect();
        }

        static::$cachedConnections = [];

        RefreshDatabaseState::flushState();
    }
}
