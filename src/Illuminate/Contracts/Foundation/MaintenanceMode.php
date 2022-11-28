<?php

namespace Illuminate\Contracts\Foundation;

interface MaintenanceMode
{
    /**
     * Marks the application in "pre-maintenance" mode so views can be pre-render checking for maintenance mode.
     *
     * @param  bool  $prerender
     * @return void
     */
    public function prerenderMaintenance(bool $prerender): void;

    /**
     * Determine if the application needs to pre-render views for maintenance mode.
     *
     * @return bool
     */
    public function needsPrerender(): bool;

    /**
     * Take the application down for maintenance.
     *
     * @param  array  $payload
     * @return void
     */
    public function activate(array $payload): void;

    /**
     * Take the application out of maintenance.
     *
     * @return void
     */
    public function deactivate(): void;

    /**
     * Determine if the application is currently down for maintenance.
     *
     * @return bool
     */
    public function active(): bool;

    /**
     * Get the data array which was provided when the application was placed into maintenance.
     *
     * @return array
     */
    public function data(): array;
}
