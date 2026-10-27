<?php

namespace Illuminate\Foundation;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\MaintenanceMode;
use Illuminate\Foundation\Events\MaintenanceModeDisabled;
use Illuminate\Support\Carbon;

class ArrayMaintenanceMode implements MaintenanceMode
{
    /**
     * Indicates if maintenance mode is currently active.
     *
     * @var bool
     */
    protected $active = false;

    /**
     * The payload provided when maintenance mode was activated.
     *
     * @var array
     */
    protected $payload = [];

    /**
     * The event dispatcher instance.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher|null
     */
    protected $events;

    /**
     * Create a new array based maintenance mode implementation.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher|null  $events
     */
    public function __construct(?Dispatcher $events = null)
    {
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
        $this->active = true;
        $this->payload = $payload;
    }

    /**
     * Take the application out of maintenance.
     *
     * @return void
     */
    public function deactivate(): void
    {
        $this->active = false;
        $this->payload = [];
    }

    /**
     * Determine if the application is currently down for maintenance.
     *
     * @return bool
     */
    public function active(): bool
    {
        if (! $this->active) {
            return false;
        }

        $expiresAt = $this->payload['expires_at'] ?? null;

        if (is_null($expiresAt) || Carbon::now()->getTimestamp() < $expiresAt) {
            return true;
        }

        $this->deactivate();

        $this->events?->dispatch(new MaintenanceModeDisabled());

        return false;
    }

    /**
     * Get the data array which was provided when the application was placed into maintenance.
     *
     * @return array
     */
    public function data(): array
    {
        return $this->payload;
    }
}
