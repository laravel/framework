<?php

namespace Illuminate\Tests\Notifications;

use Mockery;
use PHPUnit\Framework\TestCase;
use Illuminate\Notifications\SendQueuedNotifications;

class NotificationSendQueuedNotificationTest extends TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testNotificationsCanBeSent()
    {
        $job = new SendQueuedNotifications('notifiables', 'notification');
        $manager = Mockery::mock('Illuminate\Notifications\ChannelManager');
        $manager->shouldReceive('sendNow')->once()->with('notifiables', 'notification', null);
        $job->handle($manager);
    }
}
