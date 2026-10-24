<?php

namespace Illuminate\Tests\Redis\Fixtures;

use RuntimeException;

// Stands in for \RedisCluster, keeping the by-reference cursor of scan() that a double cannot.
class FakeRedisCluster
{
    /**
     * The calls made to scan(), as [cursor, node, pattern, count].
     */
    public array $scans = [];

    /**
     * The calls made to flushdb() and rawCommand().
     */
    public array $commands = [];

    /**
     * The number of times the masters were requested.
     */
    public int $mastersRequested = 0;

    /**
     * The queued masters, one entry per request. The last entry repeats.
     */
    protected array $masters;

    /**
     * The queued scan results, as [keys, next cursor].
     */
    protected array $results = [];

    public function __construct(array ...$masters)
    {
        $this->masters = $masters;
    }

    /**
     * Queue the result of the next scan, optionally moving the cursor.
     */
    public function willScan(array|false $keys, mixed $cursor = null): static
    {
        $this->results[] = [$keys, $cursor];

        return $this;
    }

    public function _masters()
    {
        $index = min($this->mastersRequested++, count($this->masters) - 1);

        return $this->masters[$index];
    }

    public function scan(&$cursor, $node, $pattern = null, $count = 0)
    {
        $this->scans[] = [$cursor, $node, $pattern, $count];

        if ($this->results === []) {
            throw new RuntimeException('Unexpected scan call.');
        }

        [$keys, $next] = array_shift($this->results);

        if ($next !== null) {
            $cursor = $next;
        }

        return $keys;
    }

    public function flushdb($node)
    {
        $this->commands[] = ['flushdb', $node];

        return true;
    }

    public function rawCommand($node, ...$arguments)
    {
        $this->commands[] = ['rawCommand', $node, ...$arguments];

        return true;
    }
}
