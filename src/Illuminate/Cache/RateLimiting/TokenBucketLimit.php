<?php

namespace Illuminate\Cache\RateLimiting;

use Closure;

class TokenBucketLimit extends Limit
{
    /**
     * The maximum bucket capacity.
     *
     * @var int|float
     */
    public $capacity;

    /**
     * The number of tokens refilled each second.
     *
     * @var int|float
     */
    public $refillPerSecond;

    /**
     * The token cost per request.
     *
     * @var (\Closure(\Illuminate\Http\Request): int|float)|int|float
     */
    public $cost = 1;

    /**
     * Create a new token bucket limit instance.
     *
     * @param  mixed  $key
     * @param  int|float  $capacity
     * @param  int|float  $refillPerSecond
     */
    public function __construct($key = '', int|float $capacity = 60, int|float $refillPerSecond = 1)
    {
        parent::__construct($key, (int) ceil($capacity), 1);

        $this->usesTokenBucket = true;
        $this->capacity = $capacity;
        $this->refillPerSecond = $refillPerSecond;
    }

    /**
     * Set the token cost per request.
     *
     * @param  (\Closure(\Illuminate\Http\Request): int|float)|int|float  $cost
     * @return $this
     */
    public function cost(Closure|int|float $cost)
    {
        $this->cost = $cost;

        return $this;
    }
}
