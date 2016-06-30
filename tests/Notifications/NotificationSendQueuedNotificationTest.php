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
        $job = new SendQueuedNotifications([$notification = new Illuminate\Notifications\Channels\Notification([])]);
        $manager = Mockery::mock('Illuminate\Notifications\ChannelManager');
        $manager->shouldReceive('send')->once()->with($notification);
        $job->handle($manager);
    }
}
