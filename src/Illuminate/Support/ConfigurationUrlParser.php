<?php

namespace Illuminate\Support;

use InvalidArgumentException;

class ConfigurationUrlParser
{
    /**
     * The drivers aliases map.
     *
     * @var array<string, string>
     */
    protected static $driverAliases = [
        'mssql' => 'sqlsrv',
        'mysql2' => 'mysql', // RDS
        'postgres' => 'pgsql',
        'postgresql' => 'pgsql',
        'sqlite3' => 'sqlite',
        'redis' => 'tcp',
        'rediss' => 'tls',
    ];

    /**
     * The SQL Server DSN option aliases.
     *
     * @var array<string, string>
     */
    protected static $sqlServerDsnOptionAliases = [
        'app' => 'appname',
        'authentication' => 'authentication',
        'columnencryption' => 'column_encryption',
        'connectionpooling' => 'pooling',
        'encrypt' => 'encrypt',
        'keystoreauthentication' => 'key_store_authentication',
        'keystoreprincipalid' => 'key_store_principal_id',
        'keystoresecret' => 'key_store_secret',
        'logintimeout' => 'login_timeout',
        'multipleactiveresultsets' => 'multiple_active_result_sets',
        'multisubnetfailover' => 'multi_subnet_failover',
        'transactionisolation' => 'transaction_isolation',
        'trustservercertificate' => 'trust_server_certificate',
    ];

    /**
     * Parse the database configuration, hydrating options using a database configuration URL if possible.
     *
     * @param  array<string, mixed>|string  $config
     * @return array<string, mixed>
     */
    public function parseConfiguration($config)
    {
        if (is_string($config)) {
            $config = ['url' => $config];
        }

        $url = Arr::pull($config, 'url');

        if (! $url) {
            return $config;
        }

        if ($this->isSqlServerDsn($url)) {
            return $this->parseSqlServerDsnConfiguration($config, $url);
        }

        $rawComponents = $this->parseUrl($url);

        $decodedComponents = $this->parseStringsToNativeTypes(
            array_map(rawurldecode(...), $rawComponents)
        );

        return array_merge(
            $config,
            $this->getPrimaryOptions($decodedComponents),
            $this->getQueryOptions($rawComponents)
        );
    }

    /**
     * Get the primary database connection options.
     *
     * @param  array<string, mixed>  $url
     * @return array<string, mixed>
     */
    protected function getPrimaryOptions($url)
    {
        return array_filter([
            'driver' => $this->getDriver($url),
            'database' => $this->getDatabase($url),
            'host' => $url['host'] ?? null,
            'port' => $url['port'] ?? null,
            'username' => $url['user'] ?? null,
            'password' => $url['pass'] ?? null,
        ], fn ($value) => ! is_null($value));
    }

    /**
     * Get the database driver from the URL.
     *
     * @param  array<string, mixed>  $url
     * @return string|null
     */
    protected function getDriver($url)
    {
        $alias = $url['scheme'] ?? null;

        if (! $alias) {
            return;
        }

        return static::$driverAliases[$alias] ?? $alias;
    }

    /**
     * Get the database name from the URL.
     *
     * @param  array<string, mixed>  $url
     * @return string|null
     */
    protected function getDatabase($url)
    {
        $path = $url['path'] ?? null;

        return $path && $path !== '/' ? substr($path, 1) : null;
    }

    /**
     * Get all of the additional database options from the query string.
     *
     * @param  array<string, mixed>  $url
     * @return array<string, mixed>
     */
    protected function getQueryOptions($url)
    {
        $queryString = $url['query'] ?? null;

        if (! $queryString) {
            return [];
        }

        $query = [];

        parse_str($queryString, $query);

        return $this->parseStringsToNativeTypes($query);
    }

    /**
     * Determine if the given value is a SQL Server DSN.
     *
     * @param  string  $url
     * @return bool
     */
    protected function isSqlServerDsn($url)
    {
        return preg_match('#^sqlsrv:(?!//)#i', $url) === 1;
    }

    /**
     * Parse a SQL Server DSN into a database configuration.
     *
     * @param  array<string, mixed>  $config
     * @param  string  $dsn
     * @return array<string, mixed>
     */
    protected function parseSqlServerDsnConfiguration($config, $dsn)
    {
        $options = $this->parseSqlServerDsnOptions($dsn);

        [$host, $port] = $this->parseSqlServerDsnServer($options['server'] ?? null);

        if (isset($options['applicationintent'])) {
            unset($config['readonly']);
        }

        return array_merge($config, $this->getSqlServerDsnConfigurationOptions($options), array_filter([
            'driver' => 'sqlsrv',
            'database' => $options['database'] ?? null,
            'host' => $host,
            'port' => $port,
        ], fn ($value) => ! is_null($value)));
    }

    /**
     * Get the database configuration options from a SQL Server DSN.
     *
     * @param  array<string, string>  $options
     * @return array<string, mixed>
     */
    protected function getSqlServerDsnConfigurationOptions($options)
    {
        $configuration = [];

        foreach (static::$sqlServerDsnOptionAliases as $option => $alias) {
            if (array_key_exists($option, $options)) {
                $configuration[$alias] = $options[$option];
            }
        }

        foreach (['connectionpooling', 'multipleactiveresultsets'] as $option) {
            if (array_key_exists($option, $options)) {
                $configuration[static::$sqlServerDsnOptionAliases[$option]] = filter_var($options[$option], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $options[$option];
            }
        }

        if (strcasecmp($options['applicationintent'] ?? '', 'ReadOnly') === 0) {
            $configuration['readonly'] = true;
        }

        return $configuration;
    }

    /**
     * Parse the options from a SQL Server DSN.
     *
     * @param  string  $dsn
     * @return array<string, string>
     */
    protected function parseSqlServerDsnOptions($dsn)
    {
        preg_match_all(
            '#(?:^|;)\s*([^=;]+?)\s*=\s*(\{(?:[^}]|}})*\}|[^;]*)#',
            substr($dsn, strlen('sqlsrv:')),
            $matches,
            PREG_SET_ORDER
        );

        $options = [];

        foreach ($matches as $match) {
            $value = trim($match[2]);

            if (str_starts_with($value, '{') && str_ends_with($value, '}')) {
                $value = str_replace('}}', '}', substr($value, 1, -1));
            }

            $options[strtolower(trim($match[1]))] = $value;
        }

        return $options;
    }

    /**
     * Parse the host and port from a SQL Server DSN Server option.
     *
     * @param  string|null  $server
     * @return array{0: string|null, 1: int|null}
     */
    protected function parseSqlServerDsnServer($server)
    {
        if (is_null($server)) {
            return [null, null];
        }

        if (preg_match('/^(.*),\s*(\d+)$/', $server, $matches)) {
            return [trim($matches[1]), (int) $matches[2]];
        }

        return [$server, null];
    }

    /**
     * Parse the string URL to an array of components.
     *
     * @param  string  $url
     * @return array<string, mixed>
     *
     * @throws \InvalidArgumentException
     */
    protected function parseUrl($url)
    {
        $url = preg_replace('#^(sqlite3?):///#', '$1://null/', $url);

        $parsedUrl = parse_url($url);

        if ($parsedUrl === false) {
            throw new InvalidArgumentException('The database configuration URL is malformed.');
        }

        return $parsedUrl;
    }

    /**
     * Convert string casted values to their native types.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function parseStringsToNativeTypes($value)
    {
        if (is_array($value)) {
            return array_map($this->parseStringsToNativeTypes(...), $value);
        }

        if (! is_string($value)) {
            return $value;
        }

        $parsedValue = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $parsedValue;
        }

        return $value;
    }

    /**
     * Get all of the current drivers' aliases.
     *
     * @return array<string, string>
     */
    public static function getDriverAliases()
    {
        return static::$driverAliases;
    }

    /**
     * Add the given driver alias to the driver aliases array.
     *
     * @param  string  $alias
     * @param  string  $driver
     * @return void
     */
    public static function addDriverAlias($alias, $driver)
    {
        static::$driverAliases[$alias] = $driver;
    }
}
