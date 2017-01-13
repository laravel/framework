<?php

namespace Illuminate\Tests\Notifications;

use Mockery;
use PHPUnit\Framework\TestCase;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Notifications\Channels\BroadcastChannel;

class NotificationBroadcastChannelTest extends TestCase
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
        $events->shouldReceive('dispatch')->once()->with(Mockery::type('Illuminate\Notifications\Events\BroadcastNotificationCreated'));
        $channel = new BroadcastChannel($events);
        $channel->send($notifiable, $notification);
    }

    public function testNotificationIsBroadcastedOnCustomChannels()
    {
        $notification = new CustomChannelsTestNotification;
        $notification->id = 1;
        $notifiable = Mockery::mock();

        $event = new \Illuminate\Notifications\Events\BroadcastNotificationCreated(
            $notifiable, $notification, $notification->toArray($notifiable)
        );

        $channels = $event->broadcastOn();

        $this->assertEquals(new PrivateChannel('custom-channel'), $channels[0]);
    }

    public function testNotificationIsBroadcastedNow()
    {
        $notification = new TestNotificationBroadCastedNow;
        $notification->id = 1;
        $notifiable = Mockery::mock();

        $events = Mockery::mock('Illuminate\Contracts\Events\Dispatcher');
        $events->shouldReceive('dispatch')->once()->with(Mockery::on(function ($event) {
            return $event->connection == 'sync';
        }));
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

class TestNotificationBroadCastedNow extends Notification
{
    public function toArray($notifiable)
    {
        return ['invoice_id' => 1];
    }

    public function toBroadcast()
    {
        return (new \Illuminate\Notifications\Messages\BroadcastMessage([]))->onConnection('sync');
    }
}
