<?php

namespace Illuminate\Foundation;

use function storage_path;

class MaintenanceMode
{
    /**
     * Determine if the application is currently down for maintenance.
     *
     * @return bool
     */
    public function isDown(): bool
    {
        return file_exists($this->getDownFilePath());
    }

    /**
     * Determine if the application is currently up.
     *
     * @return bool
     */
    public function isUp(): bool
    {
        return $this->isDown() === false;
    }

    /**
     * Take the application down for maintenance.
     *
     * @param  array  $payload
     * @return void
     */
    public function down(array $payload): void
    {
        file_put_contents(
            $this->getDownFilePath(),
            json_encode($payload, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Take the application out of maintenance.
     *
     * @return void
     */
    public function up(): void
    {
        if ($this->isDown()) {
            unlink($this->getDownFilePath());
        }
    }

    /**
     * Get the payload which was provided while the application was placed into maintenance.
     *
     * @return array
     */
    public function getPayload(): array
    {
        return json_decode(file_get_contents($this->getDownFilePath()), true);
    }

    /**
     * Get the path where the file is stored that signals that the application is down for maintenance.
     *
     * @return string
     */
    protected function getDownFilePath(): string
    {
        return storage_path('framework/down');
    }
}
