<?php

namespace Illuminate\Contracts\Foundation;

interface MaintenanceMode
{
    /**
     * Take the application down for maintenance.
     */
    public function activate(array $payload): void;

    /**
     * Take the application out of maintenance.
     */
    public function deactivate(): void;

    /**
     * Determine if the application is currently down for maintenance.
     */
    public function active(): bool;

    /**
     * Get the data array which was provided when the application was placed into maintenance.
     */
    public function data(): array;
}
