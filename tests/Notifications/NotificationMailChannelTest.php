<?php

namespace Illuminate\Tests\Notifications;

use Mockery;
use stdClass;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\HtmlString;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class NotificationMailChannelTest extends TestCase
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

        $channel = new \Illuminate\Notifications\Channels\MailChannel(
            $mailer = Mockery::mock(\Illuminate\Contracts\Mail\Mailer::class)
        );

        $markdown = Mockery::mock(StdClass::class);
        $markdown->shouldReceive('render')->andReturn($html = new HtmlString(''));
        $markdown->shouldReceive('renderText')->andReturn($text = new HtmlString(''));

        $channel->setMarkdownResolver(function () use ($markdown) {
            return $markdown;
        });

        $views = ['html' => $html, 'text' => $text];

        $mailer->shouldReceive('send')->with($views, $data, Mockery::type('Closure'));

        $channel->send($notifiable, $notification);
    }

    public function testMessageWithSubject()
    {
        $notification = new NotificationMailChannelTestNotification;
        $notifiable = new NotificationMailChannelTestNotifiable;

        $message = $notification->toMail($notifiable);
        $data = $message->toArray();

        $channel = new \Illuminate\Notifications\Channels\MailChannel(
            $mailer = Mockery::mock(\Illuminate\Contracts\Mail\Mailer::class)
        );

        $markdown = Mockery::mock(StdClass::class);
        $markdown->shouldReceive('render')->andReturn($html = new HtmlString(''));
        $markdown->shouldReceive('renderText')->andReturn($text = new HtmlString(''));

        $channel->setMarkdownResolver(function () use ($markdown) {
            return $markdown;
        });

        $views = ['html' => $html, 'text' => $text];

        $mailer->shouldReceive('send')->with($views, $data, Mockery::on(function ($closure) {
            $mock = Mockery::mock('Illuminate\Mailer\Message');

            $mock->shouldReceive('subject')->once()->with('test subject');

            $mock->shouldReceive('to')->once()->with(['taylor@laravel.com']);

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

        $channel = new \Illuminate\Notifications\Channels\MailChannel(
            $mailer = Mockery::mock(\Illuminate\Contracts\Mail\Mailer::class)
        );

        $markdown = Mockery::mock(StdClass::class);
        $markdown->shouldReceive('render')->andReturn($html = new HtmlString(''));
        $markdown->shouldReceive('renderText')->andReturn($text = new HtmlString(''));

        $channel->setMarkdownResolver(function () use ($markdown) {
            return $markdown;
        });

        $views = ['html' => $html, 'text' => $text];

        $mailer->shouldReceive('send')->with($views, $data, Mockery::on(function ($closure) {
            $mock = Mockery::mock('Illuminate\Mailer\Message');

            $mock->shouldReceive('subject')->once()->with('Notification Mail Channel Test Notification No Subject');

            $mock->shouldReceive('to')->once()->with(['taylor@laravel.com']);

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

        $channel = new \Illuminate\Notifications\Channels\MailChannel(
            $mailer = Mockery::mock(\Illuminate\Contracts\Mail\Mailer::class)
        );

        $markdown = Mockery::mock(StdClass::class);
        $markdown->shouldReceive('render')->andReturn($html = new HtmlString(''));
        $markdown->shouldReceive('renderText')->andReturn($text = new HtmlString(''));

        $channel->setMarkdownResolver(function () use ($markdown) {
            return $markdown;
        });

        $views = ['html' => $html, 'text' => $text];

        $mailer->shouldReceive('send')->with($views, $data, Mockery::on(function ($closure) {
            $mock = Mockery::mock('Illuminate\Mailer\Message');

            $mock->shouldReceive('subject')->once();

            $mock->shouldReceive('to')->never();

            $mock->shouldReceive('to')->with(['taylor@laravel.com', 'jeffrey@laracasts.com']);

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

        $channel = new \Illuminate\Notifications\Channels\MailChannel(
            $mailer = Mockery::mock(\Illuminate\Contracts\Mail\Mailer::class)
        );

        $markdown = Mockery::mock(StdClass::class);
        $markdown->shouldReceive('render')->andReturn($html = new HtmlString(''));
        $markdown->shouldReceive('renderText')->andReturn($text = new HtmlString(''));

        $channel->setMarkdownResolver(function () use ($markdown) {
            return $markdown;
        });

        $views = ['html' => $html, 'text' => $text];

        $mailer->shouldReceive('send')->with($views, $data, Mockery::on(function ($closure) {
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

        $channel = new \Illuminate\Notifications\Channels\MailChannel(
            $mailer = Mockery::mock(\Illuminate\Contracts\Mail\Mailer::class)
        );

        $markdown = Mockery::mock(StdClass::class);
        $markdown->shouldReceive('render')->andReturn($html = new HtmlString(''));
        $markdown->shouldReceive('renderText')->andReturn($text = new HtmlString(''));

        $channel->setMarkdownResolver(function () use ($markdown) {
            return $markdown;
        });

        $views = ['html' => $html, 'text' => $text];

        $mailer->shouldReceive('send')->with($views, $data, Mockery::on(function ($closure) {
            $mock = Mockery::mock('Illuminate\Mailer\Message');

            $mock->shouldReceive('subject')->once();

            $mock->shouldReceive('to')->once();

            $mock->shouldReceive('from')->with('test@mail.com', null);

            $closure($mock);

            return true;
        }));

        $channel->send($notifiable, $notification);
    }

    public function testMessageWithReplyToAddress()
    {
        $notification = new NotificationMailChannelTestNotificationWithReplyToAddress;
        $notifiable = new NotificationMailChannelTestNotifiable;

        $message = $notification->toMail($notifiable);
        $data = $message->toArray();

        $channel = new \Illuminate\Notifications\Channels\MailChannel(
            $mailer = Mockery::mock(\Illuminate\Contracts\Mail\Mailer::class)
        );

        $markdown = Mockery::mock(StdClass::class);
        $markdown->shouldReceive('render')->andReturn($html = new HtmlString(''));
        $markdown->shouldReceive('renderText')->andReturn($text = new HtmlString(''));

        $channel->setMarkdownResolver(function () use ($markdown) {
            return $markdown;
        });

        $views = ['html' => $html, 'text' => $text];

        $mailer->shouldReceive('send')->with($views, $data, Mockery::on(function ($closure) {
            $mock = Mockery::mock('Illuminate\Mailer\Message');

            $mock->shouldReceive('subject')->once();

            $mock->shouldReceive('to')->once();

            $mock->shouldReceive('replyTo')->with('test@mail.com', 'Test Man');

            $closure($mock);

            return true;
        }));

        $channel->send($notifiable, $notification);
    }

    public function testMessageWithReplyToAddressAndNoName()
    {
        $notification = new NotificationMailChannelTestNotificationWithReplyToAddressNoName;
        $notifiable = new NotificationMailChannelTestNotifiable;

        $message = $notification->toMail($notifiable);
        $data = $message->toArray();

        $channel = new \Illuminate\Notifications\Channels\MailChannel(
            $mailer = Mockery::mock(\Illuminate\Contracts\Mail\Mailer::class)
        );

        $markdown = Mockery::mock(StdClass::class);
        $markdown->shouldReceive('render')->andReturn($html = new HtmlString(''));
        $markdown->shouldReceive('renderText')->andReturn($text = new HtmlString(''));

        $channel->setMarkdownResolver(function () use ($markdown) {
            return $markdown;
        });

        $views = ['html' => $html, 'text' => $text];

        $mailer->shouldReceive('send')->with($views, $data, Mockery::on(function ($closure) {
            $mock = Mockery::mock('Illuminate\Mailer\Message');

            $mock->shouldReceive('subject')->once();

            $mock->shouldReceive('to')->once();

            $mock->shouldReceive('replyTo')->with('test@mail.com', null);

            $closure($mock);

            return true;
        }));

        $channel->send($notifiable, $notification);
    }

    public function testMessageWithPriority()
    {
        $notification = new NotificationMailChannelTestNotificationWithPriority;
        $notifiable = new NotificationMailChannelTestNotifiable;

        $message = $notification->toMail($notifiable);
        $data = $message->toArray();

        $channel = new \Illuminate\Notifications\Channels\MailChannel(
            $mailer = Mockery::mock(\Illuminate\Contracts\Mail\Mailer::class)
        );

        $markdown = Mockery::mock(StdClass::class);
        $markdown->shouldReceive('render')->andReturn($html = new HtmlString(''));
        $markdown->shouldReceive('renderText')->andReturn($text = new HtmlString(''));

        $channel->setMarkdownResolver(function () use ($markdown) {
            return $markdown;
        });

        $views = ['html' => $html, 'text' => $text];

        $mailer->shouldReceive('send')->with($views, $data, Mockery::on(function ($closure) {
            $mock = Mockery::mock('Illuminate\Mailer\Message');

            $mock->shouldReceive('subject')->once();

            $mock->shouldReceive('to')->once()->with(['taylor@laravel.com']);

            $mock->shouldReceive('setPriority')->once()->with(1);

            $closure($mock);

            return true;
        }));

        $channel->send($notifiable, $notification);
    }

    public function testMessageWithMailableContract()
    {
        $notification = new NotificationMailChannelTestNotificationWithMailableContract;
        $notifiable = new NotificationMailChannelTestNotifiable;

        $channel = new \Illuminate\Notifications\Channels\MailChannel(
            $mailer = Mockery::mock(\Illuminate\Contracts\Mail\Mailer::class)
        );

        $mailer->shouldReceive('send')->once();

        $channel->send($notifiable, $notification);
    }
}

class NotificationMailChannelTestNotifiable
{
    use \Illuminate\Notifications\Notifiable;

    public $email = 'taylor@laravel.com';
}

class NotificationMailChannelTestNotifiableMultipleEmails
{
    use \Illuminate\Notifications\Notifiable;

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

class NotificationMailChannelTestNotificationWithToAddress extends Notification
{
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->to('jeffrey@laracasts.com');
    }
}

class NotificationMailChannelTestNotificationWithCcEmails extends Notification
{
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->cc(['cc1@email.com', 'cc2@email.com']);
    }
}

class NotificationMailChannelTestNotificationWithReplyToAddress extends Notification
{
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->replyTo('test@mail.com', 'Test Man');
    }
}

class NotificationMailChannelTestNotificationWithReplyToAddressNoName extends Notification
{
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->replyTo('test@mail.com');
    }
}

class NotificationMailChannelTestNotificationWithPriority extends Notification
{
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->priority(1);
    }
}

class NotificationMailChannelTestNotificationWithMailableContract extends Notification
{
    public function toMail($notifiable)
    {
        $mock = Mockery::mock(\Illuminate\Contracts\Mail\Mailable::class);

        $mock->shouldReceive('send')->once()->with(Mockery::on(function ($mailer) {
            if (! $mailer instanceof \Illuminate\Contracts\Mail\Mailer) {
                return false;
            }

            $mailer->send('notifications::email-plain');

            return true;
        }));

        return $mock;
    }
}
