<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\ArrayMaintenanceMode;
use Illuminate\Foundation\Events\MaintenanceModeDisabled;
use Illuminate\Support\Carbon;
use Mockery;
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

    public function test_it_is_deactivated_once_expired()
    {
        Carbon::setTestNow('2026-01-01 00:00:00');

        $events = Mockery::spy(Dispatcher::class);

        $manager = new ArrayMaintenanceMode($events);

        $manager->activate(['expires_at' => Carbon::now()->addMinute()->getTimestamp()]);
        $this->assertTrue($manager->active());

        Carbon::setTestNow('2026-01-01 00:01:00');

        $this->assertFalse($manager->active());
        $this->assertFalse($manager->active());
        $this->assertSame([], $manager->data());

        $events->shouldHaveReceived('dispatch')->once()->with(Mockery::type(MaintenanceModeDisabled::class));
    }
}
