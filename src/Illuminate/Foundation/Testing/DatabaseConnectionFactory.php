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
    public function make(array $config, $name)
    {
        $key = $name ?? $config['name'];

        if ($config['driver'] === 'sqlite') {
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
    }
}
