<?php

namespace Illuminate\Database\Connectors;

use PDO;

class DmConnector extends Connector implements ConnectorInterface
{
    /**
     * Establish a database connection.
     *
     * @param  array  $config
     * @return PDO
     */
    public function connect(array $config)
    {
        $dsn = $this->getDsn($config);

        $options = $this->getOptions($config);

        $config = $this->setCharset($config);
        $options[PDO::CHARSET] = $config['charset'];

        $connection = $this->createConnection($dsn, $config, $options);

        return $connection;
    }

    /**
     * Create a DSN string from a configuration.
     *
     * @param  array  $config
     * @return string
     */
    protected function getDsn(array $config)
    {
        // parse configuration
        $config = $this->parseConfig($config);

        // return generated dsn
        return $config['dsn'];
    }

    /**
     * Parse configurations.
     *
     * @param  array  $config
     * @return array
     */
    protected function parseConfig(array $config)
    {
        $config = $this->setHost($config);
        $config = $this->setPort($config);
        $config = $this->setDSN($config);
        $config = $this->setCharset($config);

        return $config;
    }

    /**
     * Set host from config.
     *
     * @param  array  $config
     * @return array
     */
    protected function setHost(array $config)
    {
        $config['host'] = isset($config['host']) ? $config['host'] : 'localhost';

        return $config;
    }

    /**
     * Set port from config.
     *
     * @param  array  $config
     * @return array
     */
    private function setPort(array $config)
    {
        $config['port'] = isset($config['port']) ? $config['port'] : '5236';

        return $config;
    }

    /**
     * Set dsn from config.
     *
     * @param  array  $config
     * @return array
     */
    protected function setDSN(array $config)
    {
        $config['dsn'] = "dm:host={$config['host']};";
        if ($config['port']) {
            $config['dsn'] = $config['dsn']."port={$config['port']};";
        }
        if ($config['schema']) {
            $config['dsn'] = $config['dsn']."schema={$config['schema']};";
        }

        return $config;
    }

    /**
     * Set charset from config.
     *
     * @param  array  $config
     * @return array
     */
    protected function setCharset(array $config)
    {
        if (! isset($config['charset'])) {
            $config['charset'] = 'UTF8';
        }

        return $config;
    }
}
