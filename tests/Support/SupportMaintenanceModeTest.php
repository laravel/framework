<?php

namespace Illuminate\Tests\Support;

use Illuminate\Contracts\Foundation\MaintenanceMode as MaintenanceModeContract;
use Illuminate\Support\Facades\MaintenanceMode;
use Orchestra\Testbench\TestCase;

class SupportMaintenanceModeTest extends TestCase
{
    public function testExtends()
    {
        MaintenanceMode::extend('test', fn () => new TestMaintenanceMode);

        $this->app->config->set('app.maintenance.driver', 'test');

        $this->assertInstanceOf(TestMaintenanceMode::class, $this->app->maintenanceMode());
    }
}

class TestMaintenanceMode implements MaintenanceModeContract
{
    public function activate(array $payload): void
    {
    }

    public function deactivate(): void
    {
    }

    public function active(): bool
    {
    }

    public function data(): array
    {
    }
}
