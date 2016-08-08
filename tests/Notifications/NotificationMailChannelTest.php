<?php

use Illuminate\Notifications\Messages\MailMessage;
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

        $message = $notification->asMail($notifiable);
        $data = $message->toArray();

        $channel = new Illuminate\Notifications\Channels\MailChannel(
            $mailer = Mockery::mock(Illuminate\Contracts\Mail\Mailer::class)
        );

        $mailer->shouldReceive('send')->with('notifications::email', $data, Mockery::type('Closure'));

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
    public function asMail($notifiable)
    {
        return new MailMessage;
    }
}
