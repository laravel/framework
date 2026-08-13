<?php

namespace Illuminate\Redis\Connections;

use InvalidArgumentException;

class PhpRedisClusterConnection extends PhpRedisConnection
{
    /**
     * The RedisCluster client.
     *
     * @var \RedisCluster
     */
    protected $client;

    /**
     * Scan all keys based on the given options.
     *
     * @param  mixed  $cursor
     * @param  array  $options
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    #[\Override]
    public function scan($cursor, $options = [])
    {
        if (isset($options['node'])) {
            $result = $this->client->scan($cursor,
                $options['node'],
                $options['match'] ?? '*',
                $options['count'] ?? 10
            );

            if ($result === false) {
                $result = [];
            }

            return $cursor === 0 && empty($result) ? false : [$cursor, $result];
        }

        $masters = $this->client->_masters();

        if (empty($masters)) {
            throw new InvalidArgumentException('No master nodes found in the cluster.');
        }

        [$master, $cursor] = is_string($cursor) && str_contains($cursor, ':')
            ? explode(':', $cursor, 2)
            : [0, ''];

        $master = (int) $master;
        $cursor = $cursor === '' ? null : (int) $cursor;

        while ($master < count($masters)) {
            $result = $this->client->scan($cursor,
                $masters[$master],
                $options['match'] ?? '*',
                $options['count'] ?? 10
            );

            if ((int) $cursor === 0) {
                $master++;
                $cursor = null;
            }

            if (! empty($result)) {
                return [$master.':'.$cursor, $result];
            }
        }

        return false;
    }

    /**
     * Flush the selected Redis database on all master nodes.
     *
     * @return void
     */
    public function flushdb()
    {
        $arguments = func_get_args();

        $async = strtoupper((string) ($arguments[0] ?? null)) === 'ASYNC';

        foreach ($this->client->_masters() as $master) {
            $async
                ? $this->command('rawCommand', [$master, 'flushdb', 'async'])
                : $this->command('flushdb', [$master]);
        }
    }

    /**
     * Determine if the connection is a cluster connection.
     *
     * @return bool
     */
    #[\Override]
    public function isCluster()
    {
        return true;
    }
}
