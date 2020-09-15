<?php

namespace Illuminate\Cache\RateLimiting;

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
     * The response generator callback.
     *
     * @var callable
     */
    public $responseCallback;

    /**
     * Create a new limit instance.
     *
     * @param  mixed|string  $key
     * @param  int  $maxAttempts
     * @param  int  $decayMinutes
     * @return void
     */
    public function __construct($key = '', int $maxAttempts = 60, int $decayMinutes = 1)
    {
        $this->key = $key;
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
    }

    /**
     * Create a new rate limit.
     *
     * @param  int  $maxAttempts
     * @return static
     */
    public static function perMinute($maxAttempts)
    {
        return new static('', $maxAttempts);
    }

    /**
     * Create a new unlimited rate limit.
     *
     * @return static
     */
    public static function none()
    {
        return new Unlimited;
    }

    /**
     * Set the key of the rate limit.
     *
     * @param  string  $key
     * @return $this
     */
    public function by($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Set the callback that should generate the response when the limit is exceeded.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function response(callable $callback)
    {
        $this->responseCallback = $callback;

        return $this;
    }
}
