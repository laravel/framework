<?php

use Illuminate\Notifications\Message;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Channels\DatabaseChannel;

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
            'type' => get_class($notification),
            'level' => 'info',
            'intro' => [],
            'outro' => [],
            'action_text' => null,
            'action_url' => null,
            'read' => false,
        ]);

        $channel = new DatabaseChannel;
        $channel->send($notifiables, $notification);
    }
}

class NotificationDatabaseChannelTestNotification extends Notification
{
    public function message($notifiable)
    {
        return new Message;
    }
}
