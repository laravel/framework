<?php

namespace Illuminate\Tests\Notifications;

use Illuminate\Notifications\SendQueuedNotifications;
use Mockery;
use PHPUnit\Framework\TestCase;

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
