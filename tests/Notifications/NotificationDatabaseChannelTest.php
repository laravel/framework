<?php

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Channels\DatabaseChannel;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class NotificationDatabaseChannelTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testDatabaseChannelCreatesDatabaseRecordWithProperData()
    {
        $notification = new NotificationDatabaseChannelTestNotification;
        $notifiables = collect([$notifiable = Mockery::mock()]);

        $notifiable->shouldReceive('routeNotificationFor->create')->with([
            'id' => 1,
            'type' => get_class($notification),
            'data' => ['invoice_id' => 1],
            'read' => false,
        ]);

        $events = Mockery::mock('Illuminate\Contracts\Events\Dispatcher');
        $channel = new DatabaseChannel($events);
        $channel->send($notifiables, $notification);
    }

    public function testDatabaseNotificationCreationCanBeBroadcasted()
    {
        $notification = new NotificationDatabaseChannelTestBroadcastNotification;
        $notifiables = collect([$notifiable = Mockery::mock()]);

        $notifiable->shouldReceive('routeNotificationFor->create')->with([
            'id' => 1,
            'type' => get_class($notification),
            'data' => ['invoice_id' => 1],
            'read' => false,
        ]);

        $events = Mockery::mock('Illuminate\Contracts\Events\Dispatcher');
        $events->shouldReceive('fire')->once()->with('Illuminate\Notifications\Events\DatabaseNotificationCreated');

        $channel = new DatabaseChannel($events);
        $channel->send($notifiables, $notification);
    }
}

class NotificationDatabaseChannelTestNotification extends Notification
{
    public function toDatabase($notifiable)
    {
        return new DatabaseMessage(['id' => 1, 'invoice_id' => 1]);
    }
}

class NotificationDatabaseChannelTestBroadcastNotification extends Notification implements ShouldBroadcast
{
    public function toDatabase($notifiable)
    {
        return new DatabaseMessage(['id' => 1, 'invoice_id' => 1]);
    }
}
