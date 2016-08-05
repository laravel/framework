<?php

use Illuminate\Notifications\Message;
use Illuminate\Notifications\Notification;

class NotificationNexmoChannelTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testSmsIsSentViaNexmo()
    {
        $notifiables = collect([
            $notifiable = new NotificationNexmoChannelTestNotifiable,
        ]);
        $message = new Message($notifiable, new Notification);

        $message->introLines = ['line 1'];
        $message->actionText = 'Text';
        $message->actionUrl = 'url';
        $message->outroLines = ['line 2'];

        $channel = new Illuminate\Notifications\Channels\NexmoSmsChannel(
            $nexmo = Mockery::mock(Nexmo\Client::class), '4444444444'
        );

        $nexmo->shouldReceive('message->send')->with([
            'from' => '4444444444',
            'to' => '5555555555',
            'text' => 'line 1

Text: url

line 2',
        ]);

        $channel->send($notifiables, $message);
    }
}

class NotificationNexmoChannelTestNotifiable
{
    use Illuminate\Notifications\Notifiable;
    public $phone_number = '5555555555';
}
