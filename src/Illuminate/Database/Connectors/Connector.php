<?php

namespace Illuminate\Database\Connectors;

use PDO;
use Exception;
use Illuminate\Support\Arr;
use Doctrine\DBAL\Driver\PDOConnection;
use Illuminate\Database\DetectsLostConnections;

class Connector
{
    use DetectsLostConnections;

    /**
     * The default PDO connection options.
     *
     * @var array
     */
    protected $options = [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    /**
     * Create a new PDO connection.
     *
     * @param  string  $dsn
     * @param  array   $config
     * @param  array   $options
     * @return \PDO
     */
    public function createConnection($dsn, array $config, array $options)
    {
        list($username, $password) = [
            Arr::get($config, 'username'), Arr::get($config, 'password'),
        ];

        try {
            return $this->createPdoConnection(
                $dsn, $username, $password, $options
            );
        } catch (Exception $e) {
            return $this->tryAgainIfCausedByLostConnection(
                $e, $dsn, $username, $password, $options
            );
        }
    }

    /**
     * Create a new PDO connection instance.
     *
     * @param  string  $dsn
     * @param  string  $username
     * @param  string  $password
     * @param  array  $options
     * @return \PDO
     */
    protected function createPdoConnection($dsn, $username, $password, $options)
    {
        if (class_exists(PDOConnection::class) && ! $this->isPersistentConnection($options)) {
            return new PDOConnection($dsn, $username, $password, $options);
        }

        return new PDO($dsn, $username, $password, $options);
    }

    /**
     * Determine if the connection is persistent.
     *
     * @param  array  $options
     * @return bool
     */
    protected function isPersistentConnection($options)
    {
        return isset($options[PDO::ATTR_PERSISTENT]) &&
               $options[PDO::ATTR_PERSISTENT];
    }

    /**
     * Handle an exception that occurred during connect execution.
     *
     * @param  \Exception  $e
     * @param  string  $dsn
     * @param  string  $username
     * @param  string  $password
     * @param  array   $options
     * @return \PDO
     *
     * @throws \Exception
     */
    protected function tryAgainIfCausedByLostConnection(Exception $e, $dsn, $username, $password, $options)
    {
        if ($this->causedByLostConnection($e)) {
            return $this->createPdoConnection($dsn, $username, $password, $options);
        }

        throw $e;
    }

    /**
     * Get the PDO options based on the configuration.
     *
     * @param  array  $config
     * @return array
     */
    public function getOptions(array $config)
    {
        $options = Arr::get($config, 'options', []);

        return array_diff_key($this->options, $options) + $options;
    }

    /**
     * Get the default PDO connection options.
     *
     * @return array
     */
    public function getDefaultOptions()
    {
        return $this->options;
    }

    /**
     * Set the default PDO connection options.
     *
     * @param  array  $options
     * @return void
     */
    public function setDefaultOptions(array $options)
    {
        $this->options = $options;
    }
}
