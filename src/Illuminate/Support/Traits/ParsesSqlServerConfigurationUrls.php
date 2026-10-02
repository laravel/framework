<?php

namespace Illuminate\Support\Traits;

trait ParsesSqlServerConfigurationUrls
{
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
}
