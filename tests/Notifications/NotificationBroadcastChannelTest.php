<?php

namespace Illuminate\Tests\Notifications;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Notifications\Channels\BroadcastChannel;
use Illuminate\Notifications\Events\BroadcastNotificationCreated;
use Illuminate\Tests\App\Notifications\BroadcastNotification;
use Illuminate\Tests\App\Notifications\NotificationBroadcastOnSyncConnection;
use Illuminate\Tests\App\Notifications\NotificationWithAdditionalBroadcastData;
use Illuminate\Tests\App\Notifications\NotificationWithCustomBroadcastChannel;
use Illuminate\Tests\App\Notifications\NotificationWithCustomBroadcastType;
use Mockery;
use PHPUnit\Framework\TestCase;

class NotificationBroadcastChannelTest extends TestCase
{
    public function testDatabaseChannelCreatesDatabaseRecordWithProperData()
    {
        $notification = new BroadcastNotification;
        $notification->id = 1;
        $notifiable = Mockery::mock();

        $events = Mockery::mock(Dispatcher::class);
        $events->expects('dispatch')->with(Mockery::type(BroadcastNotificationCreated::class));
        $channel = new BroadcastChannel($events);
        $channel->send($notifiable, $notification);
    }

    public function testNotificationIsBroadcastedOnCustomChannels()
    {
        $notification = new NotificationWithCustomBroadcastChannel;
        $notification->id = 1;
        $notifiable = Mockery::mock();

        $event = new BroadcastNotificationCreated(
            $notifiable, $notification, $notification->toArray($notifiable)
        );

        $channels = $event->broadcastOn();

        $this->assertEquals(new PrivateChannel('custom-channel'), $channels[0]);
    }

    public function testNotificationIsBroadcastedWithCustomEventName()
    {
        $notification = new NotificationWithCustomBroadcastType;
        $notification->id = 1;
        $notifiable = Mockery::mock();

        $event = new BroadcastNotificationCreated(
            $notifiable, $notification, $notification->toArray($notifiable)
        );

        $eventName = $event->broadcastType();

        $this->assertSame('custom.type', $eventName);
    }

    public function testNotificationIsBroadcastedWithCustomDataType()
    {
        $notification = new NotificationWithCustomBroadcastType;
        $notification->id = 1;
        $notifiable = Mockery::mock();

        $event = new BroadcastNotificationCreated(
            $notifiable, $notification, $notification->toArray($notifiable)
        );

        $data = $event->broadcastWith();

        $this->assertSame('custom.type', $data['type']);
    }

    public function testNotificationIsBroadcastedNow()
    {
        $notification = new NotificationBroadcastOnSyncConnection;
        $notification->id = 1;
        $notifiable = Mockery::mock();

        $events = Mockery::mock(Dispatcher::class);
        $events->expects('dispatch')->with(Mockery::on(function ($event) {
            return $event->connection === 'sync';
        }));
        $channel = new BroadcastChannel($events);
        $channel->send($notifiable, $notification);
    }

    public function testNotificationIsBroadcastedWithCustomAdditionalPayload()
    {
        $notification = new NotificationWithAdditionalBroadcastData;
        $notification->id = 1;
        $notifiable = Mockery::mock();

        $event = new BroadcastNotificationCreated(
            $notifiable, $notification, $notification->toArray($notifiable)
        );

        $data = $event->broadcastWith();

        $this->assertArrayHasKey('additional', $data);
    }
}
