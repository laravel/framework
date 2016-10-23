<?php

namespace Illuminate\Database\Connectors;

use PDO;

class PostgresConnector extends Connector implements ConnectorInterface
{
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
    ];

    /**
     * Establish a database connection.
     *
     * @param  array  $config
     * @return \PDO
     */
    public function connect(array $config)
    {
        // First we'll create the basic DSN and connection instance connecting to the
        // using the configuration option specified by the developer. We will also
        // set the default character set on the connections to UTF-8 by default.
        $dsn = $this->getDsn($config);

        $options = $this->getOptions($config);

        $connection = $this->createConnection($dsn, $config, $options);

        $charset = $config['charset'];

        $connection->prepare("set names '$charset'")->execute();

        // Next, we will check to see if a timezone has been specified in this config
        // and if it has we will issue a statement to modify the timezone with the
        // database. Setting this DB timezone is an optional configuration item.
        if (isset($config['timezone'])) {
            $timezone = $config['timezone'];

            $connection->prepare("set time zone '$timezone'")->execute();
        }

        // Unlike MySQL, Postgres allows the concept of "schema" and a default schema
        // may have been specified on the connections. If that is the case we will
        // set the default schema search paths to the specified database schema.
        if (isset($config['schema'])) {
            $schema = $this->formatSchema($config['schema']);

            $connection->prepare("set search_path to {$schema}")->execute();
        }

        // Postgres allows an application_name to be set by the user and this name is
        // used to when monitoring the application with pg_stat_activity. So we'll
        // determine if the option has been specified and run a statement if so.
        if (isset($config['application_name'])) {
            $applicationName = $config['application_name'];

            $connection->prepare("set application_name to '$applicationName'")->execute();
        }

        return $connection;
    }

    /**
     * Create a DSN string from a configuration.
     *
     * @param  array   $config
     * @return string
     */
    protected function getDsn(array $config)
    {
        // First we will create the basic DSN setup as well as the port if it is in
        // in the configuration options. This will give us the basic DSN we will
        // need to establish the PDO connections and return them back for use.
        extract($config, EXTR_SKIP);

        $host = isset($host) ? "host={$host};" : '';

        $dsn = "pgsql:{$host}dbname={$database}";

        // If a port was specified, we will add it to this Postgres DSN connections
        // format. Once we have done that we are ready to return this connection
        // string back out for usage, as this has been fully constructed here.
        if (isset($config['port'])) {
            $dsn .= ";port={$port}";
        }

        if (isset($config['sslmode'])) {
            $dsn .= ";sslmode={$sslmode}";
        }

        if (isset($config['sslcert'])) {
            $dsn .= ";sslcert={$sslcert}";
        }

        if (isset($config['sslkey'])) {
            $dsn .= ";sslkey={$sslkey}";
        }

        if (isset($config['sslrootcert'])) {
            $dsn .= ";sslrootcert={$sslrootcert}";
        }

        return $dsn;
    }

    /**
     * Format the schema for the DSN.
     *
     * @param  array|string  $schema
     * @return string
     */
    protected function formatSchema($schema)
    {
        if (is_array($schema)) {
            return '"'.implode('", "', $schema).'"';
        } else {
            return '"'.$schema.'"';
        }
    }
}
