<?php

namespace Illuminate\Security\Sensors;

use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Security\IdsSensor;

class RateLimitingSensor extends IdsSensor
{
    /**
     * The detection weight of this sensor.
     *
     * @var int
     */
    protected $weight = 3;

    /**
     * The description of this sensor.
     *
     * @var string
     */
    protected $description = 'Detected abnormal rate of requests';

    /**
     * The RateLimiter instance.
     *
     * @var \Illuminate\Cache\RateLimiter
     */
    protected $limiter;

    /**
     * The maximum number of allowed attempts within the time window.
     *
     * @var int
     */
    protected $maxAttempts = 60;

    /**
     * The time window in seconds.
     *
     * @var int
     */
    protected $decaySeconds = 60;

    /**
     * Create a new rate limiting sensor instance.
     *
     * @param  \Illuminate\Cache\RateLimiter  $limiter
     * @return void
     */
    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Detect if this sensor has identified a threat.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function detect(Request $request): bool
    {
        $key = $this->resolveRequestSignature($request);

        $maxAttempts = $this->resolveMaxAttempts($request);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return true;
        }

        $this->limiter->hit($key, $this->decaySeconds);

        return false;
    }

    /**
     * Resolve the request signature for the limiter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function resolveRequestSignature(Request $request): string
    {
        return sha1(
            'ids_rate_limiting|'.
            $request->ip().'|'.
            $request->method().'|'.
            $request->route()?->getDomain() ?? 'none'
        );
    }

    /**
     * Resolve the maximum number of attempts for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return int
     */
    protected function resolveMaxAttempts(Request $request): int
    {
        return $this->maxAttempts;
    }

    /**
     * Set the maximum number of attempts within the time window.
     *
     * @param  int  $maxAttempts
     * @return $this
     */
    public function setMaxAttempts(int $maxAttempts)
    {
        $this->maxAttempts = $maxAttempts;

        return $this;
    }

    /**
     * Set the time window in seconds.
     *
     * @param  int  $decaySeconds
     * @return $this
     */
    public function setDecaySeconds(int $decaySeconds)
    {
        $this->decaySeconds = $decaySeconds;

        return $this;
    }
} 