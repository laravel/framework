<?php

namespace Illuminate\Database\Connectors\Concerns;

use Illuminate\Support\Arr;
use PDO;

trait ConfiguresPooledConnections
{
    /**
     * Get the direct configuration for a connection.
     *
     * @param  array  $config
     * @return array
     */
    protected function getDirectConfig(array $config)
    {
        return $this->mergeDirectConfig(
            $config, $this->getReadWriteConfig($config, 'direct')
        );
    }

    /**
     * Merge a configuration for a direct connection.
     *
     * @param  array  $config
     * @param  array  $merge
     * @return array
     */
    protected function mergeDirectConfig(array $config, array $merge)
    {
        $direct = Arr::except(array_merge($config, $merge), [
            'read', 'write', 'direct', 'pooled', 'connect_via_database', 'connect_via_port',
        ]);

        if (! isset($direct['options']) || ! is_array($direct['options'])) {
            $direct['options'] = [];
        }

        $directEmulatePreparesConfigured = isset($merge['options']) &&
            is_array($merge['options']) &&
            array_key_exists(PDO::ATTR_EMULATE_PREPARES, $merge['options']);

        if (! $directEmulatePreparesConfigured) {
            $direct['options'][PDO::ATTR_EMULATE_PREPARES] = false;
        }

        return $direct;
    }

    /**
     * Ensure pooled PostgreSQL connections are configured correctly.
     *
     * @param  array  $config
     * @return array
     */
    protected function ensurePooledPostgresIsProperlyConfigured(array $config)
    {
        $hasDirectConnection = ! empty($config['direct']);

        if (! $hasDirectConnection && ($config['pooled'] ?? false) !== true) {
            return $config;
        }

        if ($hasDirectConnection) {
            $config['pooled'] = true;
        }

        if (! $hasDirectConnection && ($config['pooled'] ?? false) === true) {
            trigger_error(
                "Database connection [{$config['name']}] sets 'pooled' => true without a 'direct' endpoint; migrations and DDL will still traverse the transaction pooler.",
                E_USER_WARNING
            );
        }

        $config = $this->withEmulatedPrepares($config);

        foreach (['read', 'write'] as $type) {
            if (! isset($config[$type])) {
                continue;
            }

            if (isset($config[$type][0])) {
                foreach ($config[$type] as $index => $connection) {
                    if (isset($connection['options'])) {
                        $config[$type][$index] = $this->withEmulatedPrepares($connection);
                    }
                }
            } elseif (isset($config[$type]['options'])) {
                $config[$type] = $this->withEmulatedPrepares($config[$type]);
            }
        }

        return $config;
    }

    /**
     * Stamp emulated prepares onto a connection configuration when not explicit.
     *
     * @param  array  $config
     * @return array
     */
    protected function withEmulatedPrepares(array $config)
    {
        if (! isset($config['options']) || ! is_array($config['options'])) {
            $config['options'] = [];
        }

        if (! array_key_exists(PDO::ATTR_EMULATE_PREPARES, $config['options'] ?? [])) {
            $config['options'][PDO::ATTR_EMULATE_PREPARES] = true;
        }

        return $config;
    }

    /**
     * Determine if the configuration has a direct PostgreSQL connection.
     *
     * @param  array  $config
     * @return bool
     */
    protected function hasDirectConnection(array $config)
    {
        return ($config['driver'] ?? null) === 'pgsql' && ! empty($config['direct']);
    }
}
