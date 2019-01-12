<?php

namespace Illuminate\Foundation\Testing\Concerns;

class RedisConnectionFailedOnceWithDefaults
{
    /**
     * Indicate connection failed if redis is not available.
     *
     * @var bool
     */
    public static $skip = false;
}
