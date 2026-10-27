<?php

namespace Illuminate\Foundation;

use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\MaintenanceMode;
use Illuminate\Foundation\Events\MaintenanceModeDisabled;
use Illuminate\Support\Carbon;

class CacheBasedMaintenanceMode implements MaintenanceMode
{
    /**
     * The cache factory.
     *
     * @var \Illuminate\Contracts\Cache\Factory
     */
    protected $cache;

    /**
     * The cache store that should be utilized.
     *
     * @var string
     */
    protected $store;

    /**
     * The cache key to use when storing maintenance mode information.
     *
     * @var string
     */
    protected $key;

    /**
     * The event dispatcher instance.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher|null
     */
    protected $events;

    /**
     * Create a new cache based maintenance mode implementation.
     *
     * @param  \Illuminate\Contracts\Cache\Factory  $cache
     * @param  string  $store
     * @param  string  $key
     * @param  \Illuminate\Contracts\Events\Dispatcher|null  $events
     */
    public function __construct(Factory $cache, string $store, string $key, ?Dispatcher $events = null)
    {
        $this->cache = $cache;
        $this->store = $store;
        $this->key = $key;
        $this->events = $events;
    }

    /**
     * Take the application down for maintenance.
     *
     * @param  array  $payload
     * @return void
     */
    public function activate(array $payload): void
    {
        $this->getStore()->put($this->key, $payload);
    }

    /**
     * Take the application out of maintenance.
     *
     * @return void
     */
    public function deactivate(): void
    {
        $this->getStore()->forget($this->key);
    }

    /**
     * Determine if the application is currently down for maintenance.
     *
     * @return bool
     */
    public function active(): bool
    {
        $payload = $this->getStore()->get($this->key);

        if (is_null($payload)) {
            return false;
        }

        $expiresAt = $payload['expires_at'] ?? null;

        if (is_null($expiresAt) || Carbon::now()->getTimestamp() < $expiresAt) {
            return true;
        }

        // Not every store reports whether forget() removed the key, so add() elects a single process...
        if ($this->getStore()->add("{$this->key}:expired:{$expiresAt}", true, 60)) {
            $this->getStore()->forget($this->key);

            $this->events?->dispatch(new MaintenanceModeDisabled());
        }

        return false;
    }

    /**
     * Get the data array which was provided when the application was placed into maintenance.
     *
     * @return array
     */
    public function data(): array
    {
        return $this->getStore()->get($this->key);
    }

    /**
     * Get the cache store to use.
     *
     * @return \Illuminate\Contracts\Cache\Repository
     */
    protected function getStore(): Repository
    {
        return $this->cache->store($this->store);
    }
}
