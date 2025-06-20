<?php

namespace Illuminate\Tests\Support;

use Illuminate\Contracts\Foundation\MaintenanceMode as MaintenanceModeContract;
use Illuminate\Support\Facades\MaintenanceMode;
use Orchestra\Testbench\TestCase;

class SupportMaintenanceModeTest extends TestCase
{
    public function testItExtends()
    {
        MaintenanceMode::extend('test', fn () => new TestMaintenanceMode);

        $this->app->config->set('app.maintenance.driver', 'test');

        $this->assertInstanceOf(TestMaintenanceMode::class, $this->app->maintenanceMode());
    }
}

class TestMaintenanceMode implements MaintenanceModeContract
{
    protected array $payload = [];
    protected bool $active = false;

    public function activate(array $payload): void
    {
        $this->payload = $payload;
    }

    public function deactivate(): void
    {
        $this->active = false;
    }

    public function active(): bool
    {
        $this->active = true;
    }

    public function data(): array
    {
        return $this->payload;
    }
}
