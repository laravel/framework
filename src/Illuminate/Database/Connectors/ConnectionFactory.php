<?php

namespace Illuminate\Database\Connectors;

use PDO;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Illuminate\Contracts\Container\Container;

class ConnectionFactory
{
    /**
     * The IoC container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * Create a new connection factory instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Establish a PDO connection based on the configuration.
     *
     * @param  array   $config
     * @param  string  $name
     * @return \Illuminate\Database\Connection
     */
    public function make(array $config, $name = null)
    {
        $config = $this->parseConfig($config, $name);

        if (isset($config['read'])) {
            return $this->createReadWriteConnection($config);
        }

        return $this->createSingleConnection($config);
    }

    /**
     * Create a single database connection instance.
     *
     * @param  array  $config
     * @return \Illuminate\Database\Connection
     */
    protected function createSingleConnection(array $config)
    {
        $pdo = $this->createConnector($config)->connect($config);

        return $this->createConnection($config['driver'], $pdo, $config['database'], $config['prefix'], $config);
    }

    /**
     * Create a single database connection instance.
     *
     * @param  array  $config
     * @return \Illuminate\Database\Connection
     */
    protected function createReadWriteConnection(array $config)
    {
        $connection = $this->createSingleConnection($this->getWriteConfig($config));

        return $connection->setReadPdo($this->createReadPdo($config));
    }

    /**
     * Create a new PDO instance for reading.
     *
     * @param  array  $config
     * @return \PDO
     */
    protected function createReadPdo(array $config)
    {
        $readConfig = $this->getReadConfig($config);

        return $this->createConnector($readConfig)->connect($readConfig);
    }

    /**
     * Get the read configuration for a read / write connection.
     *
     * @param  array  $config
     * @return array
     */
    protected function getReadConfig(array $config)
    {
        $readConfig = $this->getReadWriteConfig($config, 'read');

        if (isset($readConfig['host']) && is_array($readConfig['host'])) {
            $readConfig['host'] = count($readConfig['host']) > 1
                ? $readConfig['host'][array_rand($readConfig['host'])]
                : $readConfig['host'][0];
        }

        return $this->mergeReadWriteConfig($config, $readConfig);
    }

    /**
     * Get the read configuration for a read / write connection.
     *
     * @param  array  $config
     * @return array
     */
    protected function getWriteConfig(array $config)
    {
        $writeConfig = $this->getReadWriteConfig($config, 'write');

        return $this->mergeReadWriteConfig($config, $writeConfig);
    }

    /**
     * Get a read / write level configuration.
     *
     * @param  array   $config
     * @param  string  $type
     * @return array
     */
    protected function getReadWriteConfig(array $config, $type)
    {
        if (isset($config[$type][0])) {
            return $config[$type][array_rand($config[$type])];
        }

        return $config[$type];
    }

    /**
     * Merge a configuration for a read / write connection.
     *
     * @param  array  $config
     * @param  array  $merge
     * @return array
     */
    protected function mergeReadWriteConfig(array $config, array $merge)
    {
        return Arr::except(array_merge($config, $merge), ['read', 'write']);
    }

    /**
     * Parse and prepare the database configuration.
     *
     * @param  array   $config
     * @param  string  $name
     * @return array
     */
    protected function parseConfig(array $config, $name)
    {
        return Arr::add(Arr::add($config, 'prefix', ''), 'name', $name);
    }

    /**
     * Prepare a map of driver to class prefixes.
     *
     * @return array
     */
    protected function getDriverClassPrefixes()
    {
        return [
            'mysql' => 'MySql',
            'pgsql' => 'Postgres',
            'sqlite' => 'SQLite',
            'sqlsrv' => 'SqlServer',
        ];
    }

    /**
     * Create a class instance for the given driver and suffix based on the configuration.
     *
     * @param string  $driver
     * @param string  $suffix
     * @param array   $parameters
     * @param string  $namespace
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    protected function makeClassForDriver($driver, $suffix, array $parameters = null, $namespace = __NAMESPACE__)
    {
        $abstract = strtolower($suffix);
        if ($this->container->bound($key = "db.{$abstract}.{$driver}")) {
            $args = [$key];
            if (! is_null($parameters)) {
                array_push($args, $parameters);
            }

            return call_user_func_array([$this->container, 'make'], $args);
        }

        $prefixes = $this->getDriverClassPrefixes();

        if (isset($prefixes[$driver])) {
            $className = $namespace.'\\'.$prefixes[$driver].$suffix;
            $reflector = new \ReflectionClass($className);

            return $reflector->newInstanceArgs($parameters ?: []);
        }

        throw new InvalidArgumentException("Unsupported driver [{$driver}]");
    }

    /**
     * Create a connector instance based on the configuration.
     *
     * @param  array  $config
     * @return \Illuminate\Database\Connectors\ConnectorInterface
     *
     * @throws \InvalidArgumentException
     */
    public function createConnector(array $config)
    {
        if (! isset($config['driver'])) {
            throw new InvalidArgumentException('A driver must be specified.');
        }

        return $this->makeClassForDriver($config['driver'], 'Connector');
    }

    /**
     * Create a new connection instance.
     *
     * @param  string   $driver
     * @param  \PDO     $connection
     * @param  string   $database
     * @param  string   $prefix
     * @param  array    $config
     * @return \Illuminate\Database\Connection
     *
     * @throws \InvalidArgumentException
     */
    protected function createConnection($driver, PDO $connection, $database, $prefix = '', array $config = [])
    {
        return $this->makeClassForDriver(
            $driver,
            'Connection',
            [$connection, $database, $prefix, $config],
            'Illuminate\\Database'
        );
    }
}
