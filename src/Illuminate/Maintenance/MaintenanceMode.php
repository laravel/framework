<?php

namespace Illuminate\Maintenance;

class MaintenanceMode
{
    public function isDown(): bool
    {
        return file_exists($this->getDownFilePath());
    }

    public function isUp(): bool
    {
        return $this->isDown() === false;
    }

    public function down(array $payload): void
    {
        file_put_contents(
            $this->getDownFilePath(),
            json_encode($payload, JSON_PRETTY_PRINT)
        );
    }

    public function up(): void
    {
        if ($this->isDown() === false) {
            return;
        }

        unlink($this->getDownFilePath());
    }

    public function getPayload(): array
    {
        return json_decode(file_get_contents($this->getDownFilePath()), true);
    }

    private function getDownFilePath(): string
    {
        return storage_path('framework/down');
    }
}
