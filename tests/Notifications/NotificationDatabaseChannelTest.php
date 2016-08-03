<?php

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
        $notification = new Notification;
        $notifiables = collect([$notifiable = Mockery::mock()]);

        $notifiable->shouldReceive('routeNotificationFor->create')->with([
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
