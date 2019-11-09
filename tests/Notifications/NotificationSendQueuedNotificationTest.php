<?php

namespace Illuminate\Tests\Notifications;

use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\SendQueuedNotifications;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class NotificationSendQueuedNotificationTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testNotificationsCanBeSent()
    {
        $job = new SendQueuedNotifications('notifiables', 'notification');
        $manager = m::mock(ChannelManager::class);
        $manager->shouldReceive('sendNow')->once()->with('notifiables', 'notification', null);
        $job->handle($manager);
    }
}
