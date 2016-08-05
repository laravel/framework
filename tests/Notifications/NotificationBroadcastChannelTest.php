<?php

use Illuminate\Notifications\Message;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Channels\BroadcastChannel;

class NotificationBroadcastChannelTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testBroadcastChannelCreatesDatabaseRecordWithProperData()
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

        $events = Mockery::mock('Illuminate\Contracts\Events\Dispatcher');
        $events->shouldReceive('fire')->once()->with('Illuminate\Notifications\Events\DatabaseNotificationCreated');

        $channel = new BroadcastChannel($events);
        $channel->send($notifiables, $message);
    }
}
