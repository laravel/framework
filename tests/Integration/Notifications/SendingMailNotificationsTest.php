<?php

namespace Illuminate\Tests\Integration\Notifications;

use Illuminate\Contracts\Mail\Factory as MailFactory;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Mail\Markdown;
use Illuminate\Mail\Message;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Tests\Notifications\Fixtures\Models\NotifiableUser;
use Mockery;
use Orchestra\Testbench\TestCase;

class SendingMailNotificationsTest extends TestCase
{
    public $mailFactory;
    public $mailer;
    public $markdown;

    protected function defineEnvironment($app)
    {
        $this->mailFactory = Mockery::mock(MailFactory::class);
        $this->mailer = Mockery::mock(Mailer::class);
        $this->mailFactory->shouldReceive('mailer')->andReturn($this->mailer);
        $this->markdown = Mockery::mock(Markdown::class);

        $app->extend(Markdown::class, function () {
            return $this->markdown;
        });

        $app->extend(Mailer::class, function () {
            return $this->mailer;
        });

        $app->extend(MailFactory::class, function () {
            return $this->mailFactory;
        });

        $app['view']->addLocation(__DIR__.'/Fixtures');
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
            $table->string('name')->nullable();
        });
    }

    public function testMailIsSent()
    {
        $notification = new TestMailNotification;
        $notification->id = Str::uuid()->toString();

        $user = NotifiableUser::forceCreate([
            'email' => 'taylor@laravel.com',
        ]);

        $this->markdown->expects('theme')->times(2)->with('default')->andReturn($this->markdown);
        $this->markdown->expects('render')->andReturn('htmlContent');
        $this->markdown->expects('renderText')->andReturn('textContent');

        $this->setMailerSendAssertions($notification, $user, function ($closure) {
            $message = Mockery::mock(Message::class);

            $message->expects('to')->with(['taylor@laravel.com']);

            $message->expects('cc')->with('cc@deepblue.com', 'cc');

            $message->expects('bcc')->with('bcc@deepblue.com', 'bcc');

            $message->expects('from')->with('jack@deepblue.com', 'Jacques Mayol');

            $message->expects('replyTo')->with('jack@deepblue.com', 'Jacques Mayol');

            $message->expects('subject')->with('Test Mail Notification');

            $message->expects('priority')->with(1);

            $closure($message);

            return true;
        });

        $user->notify($notification);
    }

    public function testMailIsSentWithCustomTheme()
    {
        $notification = new TestMailNotificationWithCustomTheme;
        $notification->id = Str::uuid()->toString();

        $user = NotifiableUser::forceCreate([
            'email' => 'taylor@laravel.com',
        ]);

        $this->markdown->expects('theme')->times(2)->with('my-custom-theme')->andReturn($this->markdown);
        $this->markdown->expects('render')->andReturn('htmlContent');
        $this->markdown->expects('renderText')->andReturn('textContent');

        $this->setMailerSendAssertions($notification, $user, function ($closure) {
            $message = Mockery::mock(Message::class);

            $message->expects('to')->with(['taylor@laravel.com']);

            $message->expects('cc')->with('cc@deepblue.com', 'cc');

            $message->expects('bcc')->with('bcc@deepblue.com', 'bcc');

            $message->expects('from')->with('jack@deepblue.com', 'Jacques Mayol');

            $message->expects('replyTo')->with('jack@deepblue.com', 'Jacques Mayol');

            $message->expects('subject')->with('Test Mail Notification With Custom Theme');

            $message->expects('priority')->with(1);

            $closure($message);

            return true;
        });

        $user->notify($notification);
    }

    private function setMailerSendAssertions(
        Notification $notification,
        NotifiableUser $user,
        callable $callbackExpectationClosure
    ) {
        $this->mailer->expects('send')->withArgs(function (...$args) use ($notification, $user, $callbackExpectationClosure) {
            $viewArray = $args[0];

            if (! Mockery::on(fn ($closure) => $closure([]) === 'htmlContent')->match($viewArray['html'])) {
                return false;
            }

            if (! Mockery::on(fn ($closure) => $closure([]) === 'textContent')->match($viewArray['text'])) {
                return false;
            }

            $data = $args[1];

            $expected = array_merge($notification->toMail($user)->toArray(), [
                '__laravel_notification_id' => $notification->id,
                '__laravel_notification' => get_class($notification),
                '__laravel_notification_queued' => false,
            ]);

            if (array_keys($data) !== array_keys($expected)) {
                return false;
            }
            if (array_values($data) !== array_values($expected)) {
                return false;
            }

            return Mockery::on($callbackExpectationClosure)->match($args[2]);
        });
    }

    public function testMailIsSentToNamedAddress()
    {
        $notification = new TestMailNotification;
        $notification->id = Str::uuid()->toString();

        $user = NotifiableUserWithNamedAddress::forceCreate([
            'email' => 'taylor@laravel.com',
            'name' => 'Taylor Otwell',
        ]);

        $this->markdown->expects('theme')->times(2)->with('default')->andReturn($this->markdown);
        $this->markdown->expects('render')->andReturn('htmlContent');
        $this->markdown->expects('renderText')->andReturn('textContent');

        $this->setMailerSendAssertions($notification, $user, function ($closure) {
            $message = Mockery::mock(Message::class);

            $message->expects('to')->with(['taylor@laravel.com' => 'Taylor Otwell', 'foo_taylor@laravel.com']);

            $message->expects('cc')->with('cc@deepblue.com', 'cc');

            $message->expects('bcc')->with('bcc@deepblue.com', 'bcc');

            $message->expects('from')->with('jack@deepblue.com', 'Jacques Mayol');

            $message->expects('replyTo')->with('jack@deepblue.com', 'Jacques Mayol');

            $message->expects('subject')->with('Test Mail Notification');

            $message->expects('priority')->with(1);

            $closure($message);

            return true;
        });

        $user->notify($notification);
    }

    public function testMailIsSentWithSubject()
    {
        $notification = new TestMailNotificationWithSubject;
        $notification->id = Str::uuid()->toString();

        $user = NotifiableUser::forceCreate([
            'email' => 'taylor@laravel.com',
        ]);

        $this->markdown->expects('theme')->with('default')->times(2)->andReturn($this->markdown);
        $this->markdown->expects('render')->andReturn('htmlContent');
        $this->markdown->expects('renderText')->andReturn('textContent');

        $this->setMailerSendAssertions($notification, $user, function ($closure) {
            $message = Mockery::mock(Message::class);

            $message->expects('to')->with(['taylor@laravel.com']);

            $message->expects('subject')->with('mail custom subject');

            $closure($message);

            return true;
        });

        $user->notify($notification);
    }

    public function testMailIsSentToMultipleAddresses()
    {
        $notification = new TestMailNotificationWithSubject;
        $notification->id = Str::uuid()->toString();

        $user = NotifiableUserWithMultipleAddresses::forceCreate([
            'email' => 'taylor@laravel.com',
        ]);

        $this->markdown->expects('theme')->with('default')->times(2)->andReturn($this->markdown);
        $this->markdown->expects('render')->andReturn('htmlContent');
        $this->markdown->expects('renderText')->andReturn('textContent');

        $this->setMailerSendAssertions($notification, $user, function ($closure) {
            $message = Mockery::mock(Message::class);

            $message->expects('to')->with(['foo_taylor@laravel.com', 'bar_taylor@laravel.com']);

            $message->expects('subject')->with('mail custom subject');

            $closure($message);

            return true;
        });

        $user->notify($notification);
    }

    public function testMailIsSentUsingMailable()
    {
        $notification = new TestMailNotificationWithMailable;

        $user = NotifiableUser::forceCreate([
            'email' => 'taylor@laravel.com',
        ]);

        $user->notify($notification);
    }

    public function testMailIsSentUsingMailMessageWithHtmlAndPlain()
    {
        $notification = new TestMailNotificationWithHtmlAndPlain;
        $notification->id = Str::uuid()->toString();

        $user = NotifiableUser::forceCreate([
            'email' => 'taylor@laravel.com',
        ]);

        $this->mailer->expects('send')->with(
            ['html', 'plain'],
            array_merge($notification->toMail($user)->toArray(), [
                '__laravel_notification_id' => $notification->id,
                '__laravel_notification' => get_class($notification),
                '__laravel_notification_queued' => false,
            ]),
            Mockery::on(function ($closure) {
                $message = Mockery::mock(Message::class);

                $message->expects('to')->with(['taylor@laravel.com']);

                $message->expects('subject')->with('Test Mail Notification With Html And Plain');

                $closure($message);

                return true;
            })
        );

        $user->notify($notification);
    }

    public function testMailIsSentUsingMailMessageWithHtmlOnly()
    {
        $notification = new TestMailNotificationWithHtmlOnly;
        $notification->id = Str::uuid()->toString();

        $user = NotifiableUser::forceCreate([
            'email' => 'taylor@laravel.com',
        ]);

        $this->mailer->expects('send')->with(
            'html',
            array_merge($notification->toMail($user)->toArray(), [
                '__laravel_notification_id' => $notification->id,
                '__laravel_notification' => get_class($notification),
                '__laravel_notification_queued' => false,
            ]),
            Mockery::on(function ($closure) {
                $message = Mockery::mock(Message::class);

                $message->expects('to')->with(['taylor@laravel.com']);

                $message->expects('subject')->with('Test Mail Notification With Html Only');

                $closure($message);

                return true;
            })
        );

        $user->notify($notification);
    }

    public function testMailIsSentUsingMailMessageWithPlainOnly()
    {
        $notification = new TestMailNotificationWithPlainOnly;
        $notification->id = Str::uuid()->toString();

        $user = NotifiableUser::forceCreate([
            'email' => 'taylor@laravel.com',
        ]);

        $this->mailer->expects('send')->with(
            [null, 'plain'],
            array_merge($notification->toMail($user)->toArray(), [
                '__laravel_notification_id' => $notification->id,
                '__laravel_notification' => get_class($notification),
                '__laravel_notification_queued' => false,
            ]),
            Mockery::on(function ($closure) {
                $message = Mockery::mock(Message::class);

                $message->expects('to')->with(['taylor@laravel.com']);

                $message->expects('subject')->with('Test Mail Notification With Plain Only');

                $closure($message);

                return true;
            })
        );

        $user->notify($notification);
    }
}

class NotifiableUserWithNamedAddress extends NotifiableUser
{
    public function routeNotificationForMail($notification)
    {
        return [
            $this->email => $this->name,
            'foo_'.$this->email,
        ];
    }
}

class NotifiableUserWithMultipleAddresses extends NotifiableUser
{
    public function routeNotificationForMail($notification)
    {
        return [
            'foo_'.$this->email,
            'bar_'.$this->email,
        ];
    }
}

class TestMailNotification extends Notification
{
    public function via($notifiable)
    {
        return [MailChannel::class];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->priority(1)
            ->cc('cc@deepblue.com', 'cc')
            ->bcc('bcc@deepblue.com', 'bcc')
            ->from('jack@deepblue.com', 'Jacques Mayol')
            ->replyTo('jack@deepblue.com', 'Jacques Mayol')
            ->line('The introduction to the notification.')
            ->mailer('foo');
    }
}

class TestMailNotificationWithSubject extends Notification
{
    public function via($notifiable)
    {
        return [MailChannel::class];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('mail custom subject')
            ->line('The introduction to the notification.');
    }
}

class TestMailNotificationWithMailable extends Notification
{
    public function via($notifiable)
    {
        return [MailChannel::class];
    }

    public function toMail($notifiable)
    {
        $mailable = Mockery::mock(Mailable::class);

        $mailable->expects('send');

        return $mailable;
    }
}

class TestMailNotificationWithHtmlAndPlain extends Notification
{
    public function via($notifiable)
    {
        return [MailChannel::class];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->view(['html', 'plain']);
    }
}

class TestMailNotificationWithHtmlOnly extends Notification
{
    public function via($notifiable)
    {
        return [MailChannel::class];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->view('html');
    }
}

class TestMailNotificationWithPlainOnly extends Notification
{
    public function via($notifiable)
    {
        return [MailChannel::class];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->view([null, 'plain']);
    }
}

class TestMailNotificationWithCustomTheme extends Notification
{
    public function via($notifiable)
    {
        return [MailChannel::class];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->priority(1)
            ->cc('cc@deepblue.com', 'cc')
            ->bcc('bcc@deepblue.com', 'bcc')
            ->from('jack@deepblue.com', 'Jacques Mayol')
            ->replyTo('jack@deepblue.com', 'Jacques Mayol')
            ->line('The introduction to the notification.')
            ->theme('my-custom-theme')
            ->mailer('foo');
    }
}
