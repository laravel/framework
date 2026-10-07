<?php

namespace Illuminate\Tests\Integration\Notifications;

use Illuminate\Contracts\Mail\Factory as MailFactory;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Message;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Tests\Notifications\Fixtures\Models\NotifiableUser;
use JMac\Testing\Double;
use JMac\Testing\Matching\Argument;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class SendingMailNotificationsTest extends TestCase
{
    protected function defineEnvironment($app)
    {
        $app['config']->set('mail.default', 'array');
        $app['config']->set('mail.mailers.array', ['transport' => 'array']);
        $app['config']->set('mail.mailers.foo', ['transport' => 'array']);

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

        $sending = $this->captureSendingData();

        $user->notify($notification);

        $message = $this->sentMessage('foo');

        $this->assertSame([['taylor@laravel.com', '']], $this->addresses($message->getTo()));
        $this->assertSame([['cc@deepblue.com', 'cc']], $this->addresses($message->getCc()));
        $this->assertSame([['bcc@deepblue.com', 'bcc']], $this->addresses($message->getBcc()));
        $this->assertSame([['jack@deepblue.com', 'Jacques Mayol']], $this->addresses($message->getFrom()));
        $this->assertSame([['jack@deepblue.com', 'Jacques Mayol']], $this->addresses($message->getReplyTo()));
        $this->assertSame('Test Mail Notification', $message->getSubject());
        $this->assertSame(1, $message->getPriority());
        $this->assertStringContainsString('The introduction to the notification.', $message->getHtmlBody());
        $this->assertStringContainsString('The introduction to the notification.', $message->getTextBody());

        $this->assertSame([
            '__laravel_notification_id' => $notification->id,
            '__laravel_notification' => TestMailNotification::class,
            '__laravel_notification_queued' => false,
        ], Arr::only($sending->data, [
            '__laravel_notification_id', '__laravel_notification', '__laravel_notification_queued',
        ]));
        $this->assertSame($notification->toMail($user)->toArray(), Arr::except($sending->data, [
            'message', 'mailer', '__laravel_notification_id', '__laravel_notification', '__laravel_notification_queued',
        ]));
    }

    public function testMailIsSentWithCustomTheme()
    {
        $notification = new TestMailNotificationWithCustomTheme;
        $notification->id = Str::uuid()->toString();

        $user = NotifiableUser::forceCreate([
            'email' => 'taylor@laravel.com',
        ]);

        $user->notify($notification);

        $message = $this->sentMessage('foo');

        $this->assertSame('Test Mail Notification With Custom Theme', $message->getSubject());
        $this->assertStringContainsString('#123456', $message->getHtmlBody());
    }

    public function testMailUsesDefaultThemeWhenNoneIsGiven()
    {
        $user = NotifiableUser::forceCreate([
            'email' => 'taylor@laravel.com',
        ]);

        $user->notify(new TestMailNotificationWithSubject);

        $this->assertStringNotContainsString('#123456', $this->sentMessage()->getHtmlBody());
    }

    public function testMailIsSentToNamedAddress()
    {
        $user = NotifiableUserWithNamedAddress::forceCreate([
            'email' => 'taylor@laravel.com',
            'name' => 'Taylor Otwell',
        ]);

        // A real Message cannot take this mixed named/unnamed address array
        // (see Message::addAddresses), so the address handoff is pinned here.
        $this->expectMailerSend(
            fn ($view) => $this->assertIsArray($view),
            function ($message) {
                $message->expects('to')->with(['taylor@laravel.com' => 'Taylor Otwell', 'foo_taylor@laravel.com']);
                $message->expects('subject')->with('mail custom subject');
            }
        );

        $user->notify(new TestMailNotificationWithSubject);
    }

    public function testMailIsSentWithSubject()
    {
        $notification = new TestMailNotificationWithSubject;
        $notification->id = Str::uuid()->toString();

        $user = NotifiableUser::forceCreate([
            'email' => 'taylor@laravel.com',
        ]);

        $user->notify($notification);

        $message = $this->sentMessage();

        $this->assertSame([['taylor@laravel.com', '']], $this->addresses($message->getTo()));
        $this->assertSame('mail custom subject', $message->getSubject());
    }

    public function testMailIsSentToMultipleAddresses()
    {
        $notification = new TestMailNotificationWithSubject;
        $notification->id = Str::uuid()->toString();

        $user = NotifiableUserWithMultipleAddresses::forceCreate([
            'email' => 'taylor@laravel.com',
        ]);

        $user->notify($notification);

        $message = $this->sentMessage();

        $this->assertSame([
            ['foo_taylor@laravel.com', ''],
            ['bar_taylor@laravel.com', ''],
        ], $this->addresses($message->getTo()));
        $this->assertSame('mail custom subject', $message->getSubject());
    }

    public function testMailIsSentUsingMailable()
    {
        $notification = new TestMailNotificationWithMailable;

        $user = NotifiableUser::forceCreate([
            'email' => 'taylor@laravel.com',
        ]);

        $user->notify($notification);

        $message = $this->sentMessage();

        $this->assertSame([['taylor@laravel.com', '']], $this->addresses($message->getTo()));
        $this->assertSame('Mailable Subject', $message->getSubject());
        $this->assertStringContainsString('mailable body', $message->getHtmlBody());
    }

    public function testMailIsSentUsingMailMessageWithHtmlAndPlain()
    {
        $notification = new TestMailNotificationWithHtmlAndPlain;
        $notification->id = Str::uuid()->toString();

        $user = NotifiableUser::forceCreate([
            'email' => 'taylor@laravel.com',
        ]);

        $user->notify($notification);

        $message = $this->sentMessage();

        $this->assertSame([['taylor@laravel.com', '']], $this->addresses($message->getTo()));
        $this->assertSame('Test Mail Notification With Html And Plain', $message->getSubject());
        $this->assertSame('htmlContent', trim($message->getHtmlBody()));
        $this->assertSame('plainContent', trim($message->getTextBody()));
    }

    public function testMailIsSentUsingMailMessageWithHtmlOnly()
    {
        $notification = new TestMailNotificationWithHtmlOnly;
        $notification->id = Str::uuid()->toString();

        $user = NotifiableUser::forceCreate([
            'email' => 'taylor@laravel.com',
        ]);

        $user->notify($notification);

        $message = $this->sentMessage();

        $this->assertSame([['taylor@laravel.com', '']], $this->addresses($message->getTo()));
        $this->assertSame('Test Mail Notification With Html Only', $message->getSubject());
        $this->assertSame('htmlContent', trim($message->getHtmlBody()));
        $this->assertNull($message->getTextBody());
    }

    public function testMailIsSentUsingMailMessageWithPlainOnly()
    {
        $user = NotifiableUser::forceCreate([
            'email' => 'taylor@laravel.com',
        ]);

        // A real Mailer drops a [null, 'plain'] view (Mailer::parseView checks
        // isset($view[0])), so the view handoff is pinned here.
        $this->expectMailerSend(
            fn ($view) => $this->assertSame([null, 'plain'], $view),
            function ($message) {
                $message->expects('to')->with(['taylor@laravel.com']);
                $message->expects('subject')->with('Test Mail Notification With Plain Only');
            }
        );

        $user->notify(new TestMailNotificationWithPlainOnly);
    }

    private function expectMailerSend(callable $viewAssertion, callable $messageExpectations): void
    {
        $mailer = Double::for(Mailer::class);
        $mailer->expects('send')->with(Argument::all(function ($view, $data, $callback) use ($viewAssertion, $messageExpectations) {
            $viewAssertion($view);

            $message = Double::for(Message::class);
            $messageExpectations($message);
            $callback($message);

            return true;
        }));

        $factory = Double::for(MailFactory::class);
        $factory->expects('mailer')->returns($mailer);

        $this->app->instance(MailFactory::class, $factory);
    }

    /**
     * Capture the data of the next message being sent.
     */
    private function captureSendingData(): object
    {
        $captured = new class
        {
            public $data = [];
        };

        Event::listen(MessageSending::class, function (MessageSending $event) use ($captured) {
            $captured->data = $event->data;
        });

        return $captured;
    }

    private function sentMessage(string $mailer = 'array'): Email
    {
        $messages = $this->app['mail.manager']->mailer($mailer)->getSymfonyTransport()->messages();

        $this->assertCount(1, $messages);

        return $messages->first()->getOriginalMessage();
    }

    /**
     * @param  Address[]  $addresses
     */
    private function addresses(array $addresses): array
    {
        return array_map(fn (Address $address) => [$address->getAddress(), $address->getName()], $addresses);
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
        return new TestNotificationMailable;
    }
}

class TestNotificationMailable extends Mailable
{
    public function envelope()
    {
        return new Envelope(to: ['taylor@laravel.com'], subject: 'Mailable Subject');
    }

    public function content()
    {
        return new Content(htmlString: 'mailable body');
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
