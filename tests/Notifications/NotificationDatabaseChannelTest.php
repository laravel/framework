<?php

namespace Illuminate\Tests\Notifications;

use Illuminate\Notifications\Channels\DatabaseChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Tests\App\Notifications\DatabaseNotification;
use Illuminate\Tests\App\Notifications\DatabaseNotificationWithCustomType;
use Mockery;
use PHPUnit\Framework\TestCase;

class NotificationDatabaseChannelTest extends TestCase
{
    public function testDatabaseChannelCreatesDatabaseRecordWithProperData()
    {
        $notification = new DatabaseNotification;
        $notification->id = 1;
        $notifiable = Mockery::mock();

        $notifiable->expects('routeNotificationFor->create')->with([
            'id' => 1,
            'type' => get_class($notification),
            'data' => ['invoice_id' => 1],
            'read_at' => null,
        ]);

        $channel = new DatabaseChannel;
        $channel->send($notifiable, $notification);
    }

    public function testCorrectPayloadIsSentToDatabase()
    {
        $notification = new DatabaseNotification;
        $notification->id = 1;
        $notifiable = Mockery::mock();

        $notifiable->expects('routeNotificationFor->create')->with([
            'id' => 1,
            'type' => get_class($notification),
            'data' => ['invoice_id' => 1],
            'read_at' => null,
            'something' => 'else',
        ]);

        $channel = new ExtendedDatabaseChannel;
        $channel->send($notifiable, $notification);
    }

    public function testCustomizeTypeIsSentToDatabase()
    {
        $notification = new DatabaseNotificationWithCustomType;
        $notification->id = 1;
        $notifiable = Mockery::mock();

        $notifiable->expects('routeNotificationFor->create')->with([
            'id' => 1,
            'type' => 'MONTHLY',
            'data' => ['invoice_id' => 1],
            'read_at' => Carbon::now()->toDateTimeString(),
            'something' => 'else',
        ]);

        $channel = new ExtendedDatabaseChannel;
        $channel->send($notifiable, $notification);
    }
}

class ExtendedDatabaseChannel extends DatabaseChannel
{
    protected function buildPayload($notifiable, Notification $notification)
    {
        return array_merge(parent::buildPayload($notifiable, $notification), [
            'something' => 'else',
        ]);
    }
}
