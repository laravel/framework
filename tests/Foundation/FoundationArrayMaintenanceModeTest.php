<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Foundation\ArrayMaintenanceMode;
use PHPUnit\Framework\TestCase;

class FoundationArrayMaintenanceModeTest extends TestCase
{
    public function test_it_determines_whether_maintenance_mode_is_active()
    {
        $manager = new ArrayMaintenanceMode();

        $this->assertFalse($manager->active());

        $manager->activate(['payload']);
        $this->assertTrue($manager->active());
    }

    public function test_it_retrieves_payload()
    {
        $manager = new ArrayMaintenanceMode();

        $manager->activate(['payload']);
        $this->assertSame(['payload'], $manager->data());
    }

    public function test_it_stores_payload()
    {
        $manager = new ArrayMaintenanceMode();

        $manager->activate(['payload']);

        $this->assertTrue($manager->active());
        $this->assertSame(['payload'], $manager->data());
    }

    public function test_it_removes_payload()
    {
        $manager = new ArrayMaintenanceMode();

        $manager->activate(['payload']);
        $manager->deactivate();

        $this->assertFalse($manager->active());
        $this->assertSame([], $manager->data());
    }
}
