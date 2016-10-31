<?php

use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;
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

    public function testNotificationIsBroadcastedOnCustomChannels()
    {
        $notification = new CustomChannelsTestNotification;
        $notification->id = 1;
        $notifiable = Mockery::mock();

        $event = new Illuminate\Notifications\Events\BroadcastNotificationCreated(
            $notifiable, $notification, $notification->toArray($notifiable)
        );

        $channels = $event->broadcastOn();

        $this->assertEquals(new PrivateChannel('custom-channel'), $channels[0]);
    }
}

class NotificationBroadcastChannelTestNotification extends Notification
{
    public function toArray($notifiable)
    {
        return ['invoice_id' => 1];
    }
}

class CustomChannelsTestNotification extends Notification
{
    public function toArray($notifiable)
    {
        return ['invoice_id' => 1];
    }

    public function broadcastOn()
    {
        return [new PrivateChannel('custom-channel')];
    }
}
