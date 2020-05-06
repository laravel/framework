<?php

namespace Illuminate\Cache;

class Limit
{
    /**
     * The rate limit signature key.
     *
     * @var mixed|string
     */
    public $key;

    /**
     * The maximum number of attempts allowed within the given number of minutes.
     *
     * @var int
     */
    public $maxAttempts;

    /**
     * The number of minutes until the rate limit is reset.
     *
     * @var int
     */
    public $decayMinutes;

    /**
     * Create a new limit instance.
     *
     * @param  mixed|string  $key
     * @param  int  $maxAttempts
     * @param  int  $decayMinutes
     * @return void
     */
    public function __construct($key, int $maxAttempts, int $decayMinutes = 1)
    {
        $this->key = $key;
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
    }
}
