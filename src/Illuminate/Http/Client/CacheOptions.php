<?php


namespace Illuminate\Http\Client;

use BadMethodCallException;
use Carbon\CarbonInterface;
use DateTime;
use DateTimeInterface;

/**
 * @mixin PendingRequest
 */
class CacheOptions
{
    /**
     * @var PendingRequest|null
     */
    protected $pendingRequest;

    /**
     * @var int|null
     */
    protected $ttl = null;

    /**
     * @var string|null
     */
    protected $key = null;

    public function __construct(PendingRequest $pendingRequest = null)
    {
        $this->pendingRequest = $pendingRequest;
    }

    /**
     * Set the expiration in seconds for the cached response.
     *
     * @return $this
     */
    public function for(int $ttl)
    {
        $this->ttl = $ttl;

        return $this;
    }

    /**
     * Set the expiration based on a date instance
     *
     * @return $this
     */
    public function until(CarbonInterface $date)
    {
        $this->ttl = $date->diffInRealSeconds();

        return $this;
    }

    /**
     * Set a unique key to cache with.
     *
     * @param string $key
     */
    public function by(string $key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * The unique key to cache the response by.
     *
     * @return string|null
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * The Time-to-live for the cached response.
     *
     * @return int|null
     */
    public function getExpiry()
    {
        return $this->ttl;
    }

    /**
     * Provide the PendingRequest with our cache builder and return control to it.
     */
    public function __call($name, $arguments)
    {
        if (!$this->pendingRequest) {
            throw new BadMethodCallException("$name is not an existing method and no pending request has been set.");
        }

        return call_user_func_array([$this->pendingRequest->withCacheOptions($this), $name], $arguments);
    }

}
