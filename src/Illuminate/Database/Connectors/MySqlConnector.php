<?php

namespace Illuminate\Database\Connectors;

use PDO;

class MySqlConnector extends Connector implements ConnectorInterface
{
    /**
     * Establish a database connection.
     *
     * @param  array  $config
     * @return \PDO
     */
    public function connect(array $config)
    {
        $dsn = $this->getDsn($config);

        $options = $this->getOptions($config);

        // We need to grab the PDO options that should be used while making the brand
        // new connection instance. The PDO options control various aspects of the
        // connection's behavior, and some might be specified by the developers.
        $connection = $this->createConnection($dsn, $config, $options);

        if (! empty($config['database'])) {
            $connection->exec("use `{$config['database']}`;");
        }

        $collation = $config['collation'];

        // Next we will set the "names" and "collation" on the clients connections so
        // a correct character set will be used by this client. The collation also
        // is set on the server but needs to be set here on this client objects.
        if (isset($config['charset'])) {
            $charset = $config['charset'];

            $names = "set names '$charset'".
                (! is_null($collation) ? " collate '$collation'" : '');

            $connection->prepare($names)->execute();
        }
        // Next, we will check to see if a timezone has been specified in this config
        // and if it has we will issue a statement to modify the timezone with the
        // database. Setting this DB timezone is an optional configuration item.
        if (isset($config['timezone'])) {
            $connection->prepare(
                'set time_zone="'.$config['timezone'].'"'
            )->execute();
        }

        $this->setModes($connection, $config);

        return $connection;
    }

    /**
     * Create a DSN string from a configuration.
     *
     * Chooses socket or host/port based on the 'unix_socket' config value.
     *
     * @param  array   $config
     * @return string
     */
    protected function getDsn(array $config)
    {
        return $this->configHasSocket($config) ? $this->getSocketDsn($config) : $this->getHostDsn($config);
    }

    /**
     * Determine if the given configuration array has a UNIX socket value.
     *
     * @param  array  $config
     * @return bool
     */
    protected function configHasSocket(array $config)
    {
        return isset($config['unix_socket']) && ! empty($config['unix_socket']);
    }

    /**
     * Get the DSN string for a socket configuration.
     *
     * @param  array  $config
     * @return string
     */
    protected function getSocketDsn(array $config)
    {
        return "mysql:unix_socket={$config['unix_socket']};dbname={$config['database']}";
    }

    /**
     * Get the DSN string for a host / port configuration.
     *
     * @param  array  $config
     * @return string
     */
    protected function getHostDsn(array $config)
    {
        extract($config, EXTR_SKIP);

        return isset($port)
                        ? "mysql:host={$host};port={$port};dbname={$database}"
                        : "mysql:host={$host};dbname={$database}";
    }

    /**
     * Get the modes for the connection.
     *
     * @param  array  $config
     * @return array
     */
    protected function getModes(array $config)
    {
        if (isset($config['modes'])) {
            return (array) $config['modes'];
        }

        if ($config['strict'] === false) {
            return ['NO_ENGINE_SUBSTITUTION'];
        }

        if ($config['strict'] === true) {
            return [
                'ERROR_FOR_DIVISION_BY_ZERO',
                'NO_AUTO_CREATE_USER',
                'NO_ENGINE_SUBSTITUTION',
                'NO_ZERO_DATE',
                'NO_ZERO_IN_DATE',
                'ONLY_FULL_GROUP_BY',
                'STRICT_TRANS_TABLES',
            ];
        }
    }

    /**
     * Ensure that the SQL mode should be set.
     *
     * @param  array  $config
     * @return bool
     */
    protected function shouldSetSqlMode(array $config)
    {
        return isset($config['modes']) || isset($config['strict']);
    }

    /**
     * Set the modes for the connection.
     *
     * @param  \PDO  $connection
     * @param  array  $config
     * @return void
     */
    protected function setModes(PDO $connection, array $config)
    {
        if ($this->shouldSetSqlMode($config)) {
            $sqlModes = str_replace("'", '', implode(',', $this->getModes($config)));
            $connection->prepare("set session sql_mode='".$sqlModes."'")->execute();
        }
    }
}
