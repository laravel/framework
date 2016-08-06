<?php

use Illuminate\Notifications\Notification;

class NotificationNexmoChannelTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testSmsIsSentViaNexmo()
    {
        $notification = new NotificationNexmoChannelTestNotification;
        $notifiables = collect([
            $notifiable = new NotificationNexmoChannelTestNotifiable,
        ]);

        $notification->introLines = ['line 1'];
        $notification->actionText = 'Text';
        $notification->actionUrl = 'url';
        $notification->outroLines = ['line 2'];

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

        $channel->send($notifiables, $notification);
    }
}

class NotificationNexmoChannelTestNotifiable
{
    use Illuminate\Notifications\Notifiable;
    public $phone_number = '5555555555';
}

class NotificationNexmoChannelTestNotification extends Notification
{
    public function message($notifiable)
    {
        return $this->line('line 1')
                    ->action('Text', 'url')
                    ->line('line 2');
    }
}
