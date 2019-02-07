<?php

namespace Illuminate\Tests\Notifications;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\SendQueuedNotifications;

class NotificationSendQueuedNotificationTest extends TestCase
{
    public function tearDown(): void
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
