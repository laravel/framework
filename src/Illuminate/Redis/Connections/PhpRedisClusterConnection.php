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

        // Restore the active node and its untouched phpredis cursor between calls...
        if (is_string($cursor) && str_starts_with($cursor, 'laravel:')) {
            [$scanned, $node, $cursor, $initialCursor] = json_decode(
                base64_decode(substr($cursor, 8), true), true, flags: JSON_THROW_ON_ERROR
            );
        } else {
            $scanned = [];
            $node = null;
            $initialCursor = $cursor;
        }

        // Continue with an unscanned master if the active node disappeared during a failover...
        if ($node !== null && ! in_array($node, $masters, true)) {
            $node = null;
            $cursor = $initialCursor;
        }

        while (true) {
            // Advance to the next unscanned master and start its node-local cursor...
            if ($node === null) {
                $node = current(array_filter($masters, fn ($master) => ! in_array($master, $scanned, true)));

                if ($node === false) {
                    return false;
                }

                $cursor = $initialCursor;
            }

            $result = $this->client->scan($cursor,
                $node,
                $options['match'] ?? '*',
                $options['count'] ?? 10
            );

            if ((string) $cursor === '0') {
                $scanned[] = $node;
                $node = null;
            }

            if (! empty($result)) {
                $remainingMasters = array_filter($masters, fn ($master) => ! in_array($master, $scanned, true));

                // Preserve the caller's null or zero sentinel so existing scan loops terminate...
                if ($node === null && empty($remainingMasters)) {
                    return [$initialCursor, $result];
                }

                return ['laravel:'.base64_encode(json_encode([
                    $scanned, $node, $cursor, $initialCursor,
                ], JSON_THROW_ON_ERROR)), $result];
            }

            if ($node !== null) {
                return ['laravel:'.base64_encode(json_encode([
                    $scanned, $node, $cursor, $initialCursor,
                ], JSON_THROW_ON_ERROR)), []];
            }
        }
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
