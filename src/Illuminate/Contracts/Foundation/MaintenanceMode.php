<?php

namespace Illuminate\Contracts\Foundation;

interface MaintenanceMode
{
    /**
     * Determine if the application is currently down for maintenance.
     *
     * @return bool
     */
    public function isDown(): bool;

    /**
     * Determine if the application is currently up.
     *
     * @return bool
     */
    public function isUp(): bool;

    /**
     * Take the application down for maintenance.
     *
     * @param  array  $payload
     * @return void
     */
    public function down(array $payload): void;

    /**
     * Take the application out of maintenance.
     *
     * @return void
     */
    public function up(): void;

    /**
     * Get the payload which was provided while the application was placed into maintenance.
     *
     * @return array
     */
    public function getPayload(): array;
}
