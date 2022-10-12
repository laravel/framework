<?php

namespace Illuminate\Cache\RateLimiting;

class Limit
{
    /**
     * The rate limit signature key.
     *
     * @var mixed
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
     * @param  mixed  $key
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
     * Create a new rate limit using minutes as decay time.
     *
     * @param  int  $decayMinutes
     * @param  int  $maxAttempts
     * @return static
     */
    public static function perMinutes($decayMinutes, $maxAttempts)
    {
        return new static('', $maxAttempts, $decayMinutes);
    }

    /**
     * Create a new rate limit using hours as decay time.
     *
     * @param  int  $maxAttempts
     * @param  int  $decayHours
     * @return static
     */
    public static function perHour($maxAttempts, $decayHours = 1)
    {
        return new static('', $maxAttempts, 60 * $decayHours);
    }

    /**
     * Create a new rate limit using days as decay time.
     *
     * @param  int  $maxAttempts
     * @param  int  $decayDays
     * @return static
     */
    public static function perDay($maxAttempts, $decayDays = 1)
    {
        return new static('', $maxAttempts, 60 * 24 * $decayDays);
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
     * @param  mixed  $key
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
