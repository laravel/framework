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
        $notifiables = collect([$notifiable = Mockery::mock()]);
        $message = new Message($notifiable, new Notification);

        $notifiable->shouldReceive('routeNotificationFor->create')->with([
            'type' => get_class($message->notification),
            'level' => 'info',
            'intro' => [],
            'outro' => [],
            'action_text' => null,
            'action_url' => null,
            'read' => false,
        ]);

        $channel = new DatabaseChannel;
        $channel->send($notifiables, $message);
    }
}
