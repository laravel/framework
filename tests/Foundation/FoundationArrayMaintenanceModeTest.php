<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Foundation\ArrayMaintenanceMode;
use PHPUnit\Framework\TestCase;

class FoundationArrayMaintenanceModeTest extends TestCase
{
    public function test_it_is_inactive_by_default()
    {
        $maintenanceMode = new ArrayMaintenanceMode();

        $this->assertFalse($maintenanceMode->active());
        $this->assertSame([], $maintenanceMode->data());
    }

    public function test_it_stores_payload_when_activated()
    {
        $maintenanceMode = new ArrayMaintenanceMode();

        $maintenanceMode->activate(['payload']);

        $this->assertTrue($maintenanceMode->active());
        $this->assertSame(['payload'], $maintenanceMode->data());
    }

    public function test_it_clears_payload_when_deactivated()
    {
        $maintenanceMode = new ArrayMaintenanceMode();

        $maintenanceMode->activate(['payload']);
        $maintenanceMode->deactivate();

        $this->assertFalse($maintenanceMode->active());
        $this->assertSame([], $maintenanceMode->data());
    }
}
