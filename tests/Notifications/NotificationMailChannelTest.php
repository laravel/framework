<?php

use Illuminate\Notifications\Message;
use Illuminate\Notifications\Notification;

class NotificationMailChannelTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testMailIsSentByChannel()
    {
        $notification = new NotificationMailChannelTestNotification;
        $notifiables = collect([
            $notifiable = new NotificationMailChannelTestNotifiable,
        ]);

        $array = $notification->toArray($notifiable);
        $array['actionColor'] = 'blue';

        $channel = new Illuminate\Notifications\Channels\MailChannel(
            $mailer = Mockery::mock(Illuminate\Contracts\Mail\Mailer::class)
        );

        $mailer->shouldReceive('send')->with('notifications::email', $array, Mockery::type('Closure'));

        $channel->send($notifiables, $notification);
    }
}

class NotificationMailChannelTestNotifiable
{
    use Illuminate\Notifications\Notifiable;

    public $email = 'taylor@laravel.com';
}

class NotificationMailChannelTestNotification extends Notification
{
    public function message($notifiable)
    {
        return new Message;
    }
}
