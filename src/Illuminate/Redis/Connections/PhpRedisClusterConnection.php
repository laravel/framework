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
     * SCAN is per-node, so the cursor encodes the master being iterated and its own cursor.
     *
     * @param  mixed  $cursor
     * @param  array  $options
     * @return array|false
     *
     * @throws \InvalidArgumentException
     */
    #[\Override]
    public function scan($cursor, $options = [])
    {
        if (isset($options['node'])) {
            return $this->scanNode($cursor, $options['node'], $options);
        }

        $masters = $this->masters();

        [$node, $nodeCursor] = $this->parseScanCursor($cursor);

        while ($node < count($masters)) {
            $keys = $this->client->scan(
                $nodeCursor,
                $masters[$node],
                $options['match'] ?? '*',
                $options['count'] ?? 10
            );

            if ((int) $nodeCursor === 0) {
                $node++;
                $nodeCursor = $this->freshScanCursor();
            }

            if (! empty($keys)) {
                return [$node.':'.$nodeCursor, $keys];
            }
        }

        return false;
    }

    /**
     * Scan a single node of the cluster.
     *
     * @param  mixed  $cursor
     * @param  string|array  $node
     * @param  array  $options
     * @return array|false
     */
    protected function scanNode($cursor, $node, array $options)
    {
        $result = $this->client->scan($cursor,
            $node,
            $options['match'] ?? '*',
            $options['count'] ?? 10
        );

        if ($result === false) {
            $result = [];
        }

        return $cursor === 0 && empty($result) ? false : [$cursor, $result];
    }

    /**
     * Split a scan() cursor into a master index and that master's cursor.
     *
     * @param  mixed  $cursor
     * @return array
     */
    protected function parseScanCursor($cursor)
    {
        if (is_string($cursor) && str_contains($cursor, ':')) {
            [$node, $nodeCursor] = explode(':', $cursor, 2);

            return [(int) $node, $nodeCursor === '' ? $this->freshScanCursor() : (int) $nodeCursor];
        }

        return [0, $this->freshScanCursor()];
    }

    /**
     * Get the cursor value that starts a fresh iteration of a node.
     *
     * @return string|null
     */
    protected function freshScanCursor()
    {
        return version_compare(phpversion('redis'), '6.1.0', '>=') ? null : '0';
    }

    /**
     * Get the master nodes of the cluster.
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function masters()
    {
        return $this->client->_masters() ?: throw new InvalidArgumentException('No master nodes found in the cluster.');
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
