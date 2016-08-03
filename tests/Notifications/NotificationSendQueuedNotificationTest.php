<?php

use Illuminate\Notifications\SendQueuedNotifications;

class NotificationSendQueuedNotificationTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testNotificationsCanBeSent()
    {
        $job = new SendQueuedNotifications('notifiables', 'notification');
        $manager = Mockery::mock('Illuminate\Notifications\ChannelManager');
        $manager->shouldReceive('sendNow')->once()->with('notifiables', 'notification');
        $job->handle($manager);
    }
}
