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
        $notifiable = new NotificationMailChannelTestNotifiable;

        $message = $notification->toMail($notifiable);
        $data = $message->toArray();

        $channel = new Illuminate\Notifications\Channels\MailChannel(
            $mailer = Mockery::mock(Illuminate\Contracts\Mail\Mailer::class)
        );

        $mailer->shouldReceive('send')->with('notifications::email', $data, Mockery::type('Closure'));

        $channel->send($notifiable, $notification);
    }

    public function testMessageWithSubject()
    {
        $notification = new NotificationMailChannelTestNotification;
        $notifiable = new NotificationMailChannelTestNotifiable;

        $message = $notification->toMail($notifiable);
        $data = $message->toArray();

        $channel = new Illuminate\Notifications\Channels\MailChannel(
            $mailer = Mockery::mock(Illuminate\Contracts\Mail\Mailer::class)
        );

        $mailer->shouldReceive('send')->with('notifications::email', $data, Mockery::on(function ($closure) {
            $mock = Mockery::mock('Illuminate\Mailer\Message');

            $mock->shouldReceive('subject')->once()->with('test subject');

            $mock->shouldReceive('to')->once()->with('taylor@laravel.com');

            $mock->shouldReceive('from')->never();

            $closure($mock);

            return true;
        }));

        $channel->send($notifiable, $notification);
    }

    public function testMessageWithoutSubjectAutogeneratesSubjectFromClassName()
    {
        $notification = new NotificationMailChannelTestNotificationNoSubject;
        $notifiable = new NotificationMailChannelTestNotifiable;

        $message = $notification->toMail($notifiable);
        $data = $message->toArray();

        $channel = new Illuminate\Notifications\Channels\MailChannel(
            $mailer = Mockery::mock(Illuminate\Contracts\Mail\Mailer::class)
        );

        $mailer->shouldReceive('send')->with('notifications::email', $data, Mockery::on(function ($closure) {
            $mock = Mockery::mock('Illuminate\Mailer\Message');

            $mock->shouldReceive('subject')->once()->with('Notification Mail Channel Test Notification No Subject');

            $mock->shouldReceive('to')->once()->with('taylor@laravel.com');

            $closure($mock);

            return true;
        }));

        $channel->send($notifiable, $notification);
    }

    public function testMessageWithMultipleSenders()
    {
        $notification = new NotificationMailChannelTestNotification;
        $notifiable = new NotificationMailChannelTestNotifiableMultipleEmails;

        $message = $notification->toMail($notifiable);
        $data = $message->toArray();

        $channel = new Illuminate\Notifications\Channels\MailChannel(
            $mailer = Mockery::mock(Illuminate\Contracts\Mail\Mailer::class)
        );

        $mailer->shouldReceive('send')->with('notifications::email', $data, Mockery::on(function ($closure) {
            $mock = Mockery::mock('Illuminate\Mailer\Message');

            $mock->shouldReceive('subject')->once();

            $mock->shouldReceive('to')->never();

            $mock->shouldReceive('bcc')->with(['taylor@laravel.com', 'jeffrey@laracasts.com']);

            $closure($mock);

            return true;
        }));

        $channel->send($notifiable, $notification);
    }

    public function testMessageWithFromAddress()
    {
        $notification = new NotificationMailChannelTestNotificationWithFromAddress;
        $notifiable = new NotificationMailChannelTestNotifiable;

        $message = $notification->toMail($notifiable);
        $data = $message->toArray();

        $channel = new Illuminate\Notifications\Channels\MailChannel(
            $mailer = Mockery::mock(Illuminate\Contracts\Mail\Mailer::class)
        );

        $mailer->shouldReceive('send')->with('notifications::email', $data, Mockery::on(function ($closure) {
            $mock = Mockery::mock('Illuminate\Mailer\Message');

            $mock->shouldReceive('subject')->once();

            $mock->shouldReceive('to')->once();

            $mock->shouldReceive('from')->with('test@mail.com', 'Test Man');

            $closure($mock);

            return true;
        }));

        $channel->send($notifiable, $notification);
    }

    public function testMessageWithFromAddressAndNoName()
    {
        $notification = new NotificationMailChannelTestNotificationWithFromAddressNoName;
        $notifiable = new NotificationMailChannelTestNotifiable;

        $message = $notification->toMail($notifiable);
        $data = $message->toArray();

        $channel = new Illuminate\Notifications\Channels\MailChannel(
            $mailer = Mockery::mock(Illuminate\Contracts\Mail\Mailer::class)
        );

        $mailer->shouldReceive('send')->with('notifications::email', $data, Mockery::on(function ($closure) {
            $mock = Mockery::mock('Illuminate\Mailer\Message');

            $mock->shouldReceive('subject')->once();

            $mock->shouldReceive('to')->once();

            $mock->shouldReceive('from')->with('test@mail.com', null);

            $closure($mock);

            return true;
        }));

        $channel->send($notifiable, $notification);
    }
}

class NotificationMailChannelTestNotifiable
{
    use Illuminate\Notifications\Notifiable;

    public $email = 'taylor@laravel.com';
}

class NotificationMailChannelTestNotifiableMultipleEmails
{
    use Illuminate\Notifications\Notifiable;

    public function routeNotificationForMail()
    {
        return ['taylor@laravel.com', 'jeffrey@laracasts.com'];
    }
}

class NotificationMailChannelTestNotification extends Notification
{
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('test subject');
    }
}

class NotificationMailChannelTestNotificationNoSubject extends Notification
{
    public function toMail($notifiable)
    {
        return new MailMessage;
    }
}

class NotificationMailChannelTestNotificationWithFromAddress extends Notification
{
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->from('test@mail.com', 'Test Man');
    }
}

class NotificationMailChannelTestNotificationWithFromAddressNoName extends Notification
{
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->from('test@mail.com');
    }
}
