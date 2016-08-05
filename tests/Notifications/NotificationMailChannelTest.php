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
        $notifiables = collect([
            $notifiable = new NotificationMailChannelTestNotifiable,
        ]);
        $message = new Message($notifiable, new Notification);

        $array = $message->toArray();
        $array['actionColor'] = 'blue';

        $channel = new Illuminate\Notifications\Channels\MailChannel(
            $mailer = Mockery::mock(Illuminate\Contracts\Mail\Mailer::class)
        );

        $mailer->shouldReceive('send')->with('notifications::email', $array, Mockery::type('Closure'));

        $channel->send($notifiables, $message);
    }
}

class NotificationMailChannelTestNotifiable
{
    use Illuminate\Notifications\Notifiable;

    public $email = 'taylor@laravel.com';
}
