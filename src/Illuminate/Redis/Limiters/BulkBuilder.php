<?php

namespace Illuminate\Redis\Limiters;

use Illuminate\Contracts\Redis\LimiterTimeoutException;
use Illuminate\Support\InteractsWithTime;

class BulkBuilder
{
    use InteractsWithTime;

    /**
     * The Redis connection.
     *
     * @var \Illuminate\Redis\Connections\Connection
     */
    public $connection;

    /**
     * The name of the cache key.
     *
     * @var string
     */
    public $name;

    /**
     * The maximum number of items that can be cache.
     *
     * @var int
     */
    public $maxItemCount = 10;

    /**
     * The item.
     *
     * @var string
     */
    public $item = null;

    /**
     * force to release the cache
     *
     * @var bool
     */
    public $forceRelease = false;

    /**
     * Create a new builder instance.
     *
     * @param  \Illuminate\Redis\Connections\Connection  $connection
     * @param  string  $name
     * @return void
     */
    public function __construct($connection, $name)
    {
        $this->name = $name;
        $this->connection = $connection;
    }

    /**
     * Set the maximum number of item that can be cache.
     *
     * @param  int  $maxItemCount
     * @return $this
     */
    public function count($maxItemCount)
    {
        $this->maxItemCount = $maxItemCount;

        return $this;
    }

    /**
     * Call this item to force release the cache.
     *
     * @return $this
     */
    public function forceRelease()
    {
        $this->forceRelease = true;

        return $this;
    }

    /**
     * Add the item to cache.
     *
     * @param  string  $item
     * @return $this
     */
    public function add($item)
    {
        $this->item = $item;

        return $this;
    }

    /**
     * Execute the given callback when cached items count is equal to max item count,
     * if error raise call the failure callback.
     *
     * @param callable $callback
     * @param callable|null $failure
     * @return mixed
     * @throws LimiterTimeoutException
     */
    public function then(callable $callback, callable $failure = null)
    {
        try {
            (new Bulk($this->connection, $this->name, $this->item, $this->maxItemCount, $this->forceRelease))
                ->then($callback);
        } catch (LimiterTimeoutException $e) {
            if ($failure) {
                return $failure($e);
            }

            throw $e;
        }
    }
}
