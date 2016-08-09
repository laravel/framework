<?php

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Channels\BroadcastChannel;

class NotificationBroadcastChannelTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testDatabaseChannelCreatesDatabaseRecordWithProperData()
    {
        $notification = new NotificationBroadcastChannelTestNotification;
        $notification->id = 1;
        $notifiable = Mockery::mock();

        $events = Mockery::mock('Illuminate\Contracts\Events\Dispatcher');
        $events->shouldReceive('fire')->once()->with(Mockery::type('Illuminate\Notifications\Events\BroadcastNotificationCreated'));
        $channel = new BroadcastChannel($events);
        $channel->send($notifiable, $notification);
    }
}

class NotificationBroadcastChannelTestNotification extends Notification
{
    public function toArray($notifiable)
    {
        return ['invoice_id' => 1];
    }
}
