<?php

namespace Illuminate\Redis\Connections;

class PhpRedisClusterConnection extends PhpRedisConnection
{
    /**
     * Add one or more members to a sorted set or update its score if it already exists.
     *
     * @param  string  $key
     * @param  dynamic  $dictionary
     * @return int
     */
    public function zadd($key, ...$dictionary)
    {
        if (is_array(end($dictionary))) {
            foreach (array_pop($dictionary) as $member => $score) {
                $dictionary[] = $score;
                $dictionary[] = $member;
            }
        }
        $key = $this->applyPrefix($key);
        return $this->client->zAdd($key, ...$dictionary);
    }
    /**
     * Determine if the given keys exist.
     *
     * @param  dynamic  $keys
     * @return int
     */
    public function exists(...$keys)
    {
        $keys = collect($keys)->map(function ($key) {
            return $this->applyPrefix($key);
        });
        return $keys->reduce(function ($carry, $key) {
            return $carry + $this->client->exists($key);
        });
    }
    /**
     * Apply prefix to the given key if necessary.
     *
     * @param  string  $key
     * @return string
     */
    private function applyPrefix($key)
    {
        $prefix = (string) $this->client->getOption(\RedisCluster::OPT_PREFIX);
        return $prefix.$key;
    }
}
