<?php

namespace Illuminate\Tests\Notifications;

use Mockery;
use PHPUnit\Framework\TestCase;
use Illuminate\Notifications\SendQueuedNotifications;

class NotificationSendQueuedNotificationTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
    }

    public function testNotificationsCanBeSent(): void
    {
        $job = new SendQueuedNotifications('notifiables', 'notification');
        $manager = Mockery::mock('Illuminate\Notifications\ChannelManager');
        $manager->shouldReceive('sendNow')->once()->with('notifiables', 'notification', null);
        $job->handle($manager);
    }
}
