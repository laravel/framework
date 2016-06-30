<?php

use Illuminate\Notifications\Channels\Notification;

class NotificationDatabaseChannelTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testDatabaseChannelCreatesDatabaseRecordWithProperData()
    {
        $notification = new Notification([
            $notifiable = Mockery::mock(),
        ]);

        $notifiable->shouldReceive('routeNotificationFor->create')->with([
            'level' => 'info',
            'intro' => [],
            'outro' => [],
            'action_text' => null,
            'action_url' => null,
            'read' => false,
        ]);
    }
}
