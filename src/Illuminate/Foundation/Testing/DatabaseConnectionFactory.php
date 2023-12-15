<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Contracts\Database\Connectors\ConnectionFactory as ConnectionFactoryContract;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Support\Arr;

/**
 * @internal
 */
class DatabaseConnectionFactory implements ConnectionFactoryContract
{
    /**
     * List of cached database connections.
     *
     * @var array<string, \Illuminate\Database\Connection>
     */
    protected static array $cachedConnections = [];

    public function __construct(
        protected ConnectionFactory $factory
    ) {
        //
    }

    /**
     * Establish a PDO connection based on the configuration.
     *
     * @param  array  $config
     * @param  string|null  $name
     * @return \Illuminate\Database\Connection
     */
    public function make(array $config, $name = null)
    {
        $key = $name ?? $config['name'];

        if ($config['driver'] === 'sqlite') {
            return $this->factory->make($config, $name);
        }

        if (! isset(static::$cachedConnections[$key]) || \is_null(static::$cachedConnections[$key]->getRawPdo() ?? null)) {
            return static::$cachedConnections[$key] = $this->factory->make($config, $name);
        }

        $config = $this->parseConfig($config, $name);

        $connection = $this->createConnection(
            $config['driver'], static::$cachedConnections[$key]->getRawPdo(), $config['database'], $config['prefix'], $config
        )->setReadPdo(static::$cachedConnections[$key]->getRawReadPdo());

        return static::$cachedConnections[$key] = $connection;
    }

    /**
     * Parse and prepare the database configuration.
     *
     * @param  array  $config
     * @param  string  $name
     * @return array
     */
    protected function parseConfig(array $config, $name)
    {
        return Arr::add(Arr::add($config, 'prefix', ''), 'name', $name);
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
