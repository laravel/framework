<?php

namespace Illuminate\Tests\Notifications;

use Illuminate\Contracts\Mail\Factory as MailFactory;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Markdown;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Notification;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class MailChannelTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();

        parent::tearDown();
    }

    public function testItPopulatesMailableRecipientsWhenToIsEmpty()
    {
        $mailable = new MailChannelTestMailable;

        $mailable = $this->channel()->populateMailableRecipients(
            $mailable,
            new MailChannelNotifiable('taylor@laravel.com'),
            new MailChannelTestNotification,
        );

        $this->assertSame('taylor@laravel.com', $mailable->to[0]['address']);
    }

    public function testItDoesNotOverrideExistingMailableRecipients()
    {
        $mailable = (new MailChannelTestMailable)->to('other@laravel.com');

        $mailable = $this->channel()->populateMailableRecipients(
            $mailable,
            new MailChannelNotifiable('taylor@laravel.com'),
            new MailChannelTestNotification,
        );

        $this->assertSame('other@laravel.com', $mailable->to[0]['address']);
    }

    public function testItDoesNotOverrideMailableRecipientsDefinedOnEnvelope()
    {
        $mailable = new MailChannelTestMailableWithEnvelopeRecipient;

        $mailable = $this->channel()->populateMailableRecipients(
            $mailable,
            new MailChannelNotifiable('taylor@laravel.com'),
            new MailChannelTestNotification,
        );

        $this->assertSame([], $mailable->to);
    }

    public function testItPopulatesMultipleMailableRecipientsFromRoute()
    {
        $mailable = new MailChannelTestMailable;

        $mailable = $this->channel()->populateMailableRecipients(
            $mailable,
            new MailChannelNotifiableWithMultipleRoutes('taylor@laravel.com'),
            new MailChannelTestNotification,
        );

        $this->assertEqualsCanonicalizing(
            ['foo_taylor@laravel.com', 'bar_taylor@laravel.com'],
            array_column($mailable->to, 'address')
        );
    }

    protected function channel(): MailChannelForTesting
    {
        return new MailChannelForTesting(
            m::mock(MailFactory::class),
            m::mock(Markdown::class),
        );
    }
}

class MailChannelForTesting extends MailChannel
{
    public function populateMailableRecipients($mailable, $notifiable, $notification)
    {
        return parent::populateMailableRecipients($mailable, $notifiable, $notification);
    }
}

class MailChannelNotifiable
{
    public function __construct(public string $email)
    {
    }

    public function routeNotificationFor($driver, $notification = null)
    {
        return $driver === 'mail'
            ? $this->routeNotificationForMail($notification)
            : null;
    }

    public function routeNotificationForMail($notification)
    {
        return $this->email;
    }
}

class MailChannelNotifiableWithMultipleRoutes extends MailChannelNotifiable
{
    public function routeNotificationForMail($notification)
    {
        return [
            'foo_'.$this->email,
            'bar_'.$this->email,
        ];
    }
}

class MailChannelTestMailable extends Mailable
{
    public function build()
    {
        return $this->html('Test');
    }
}

class MailChannelTestMailableWithEnvelopeRecipient extends Mailable
{
    public function envelope()
    {
        return new \Illuminate\Mail\Mailables\Envelope(
            to: [new \Illuminate\Mail\Mailables\Address('envelope@laravel.com')],
            subject: 'Test',
        );
    }

    public function content()
    {
        return new \Illuminate\Mail\Mailables\Content(
            htmlString: 'Test',
        );
    }
}

class MailChannelTestNotification extends Notification
{
    //
}
