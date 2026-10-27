<?php

namespace Illuminate\Foundation;

use ErrorException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\MaintenanceMode as MaintenanceModeContract;
use Illuminate\Foundation\Events\MaintenanceModeDisabled;
use Illuminate\Support\Carbon;
use TypeError;

class FileBasedMaintenanceMode implements MaintenanceModeContract
{
    /**
     * The event dispatcher instance.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher|null
     */
    protected $events;

    /**
     * Create a new file based maintenance mode implementation.
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
        file_put_contents(
            $this->path(),
            json_encode($payload, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Take the application out of maintenance.
     *
     * @return void
     */
    public function deactivate(): void
    {
        if (file_exists($this->path())) {
            unlink($this->path());
        }
    }

    /**
     * Determine if the application is currently down for maintenance.
     *
     * @return bool
     */
    public function active(): bool
    {
        if (! file_exists($this->path())) {
            return false;
        }

        try {
            $expiresAt = $this->data()['expires_at'] ?? null;
        } catch (ErrorException|TypeError) {
            return file_exists($this->path());
        }

        if (is_null($expiresAt) || Carbon::now()->getTimestamp() < $expiresAt) {
            return true;
        }

        // Concurrent requests may all see the expired file, but only one can remove it...
        if (@unlink($this->path())) {
            if (is_file(storage_path('framework/maintenance.php'))) {
                @unlink(storage_path('framework/maintenance.php'));
            }

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
        return json_decode(file_get_contents($this->path()), true);
    }

    /**
     * Get the path where the file is stored that signals that the application is down for maintenance.
     *
     * @return string
     */
    protected function path(): string
    {
        return storage_path('framework/down');
    }
}
