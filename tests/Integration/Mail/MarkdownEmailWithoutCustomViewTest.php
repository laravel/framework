<?php

namespace Illuminate\Tests\Integration\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Mail;
use Orchestra\Testbench\TestCase;

class MarkdownEmailWithoutCustomViewTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('mail.driver', 'array');
    }

    public function testDefaultMarkdownEmailContainsExpectedContent()
    {
        $mailable = new DefaultMarkdownMailable;

        $mailable
            ->assertHasSubject('Subject from Mailable')
            ->assertSeeInText('Hello, world!')
            ->assertSeeInHtml('Welcome to your new Laravel application.')
            ->assertSeeInHtml('My basic content')
            ->assertSeeInHtml('Sincerely, Laravel');
    }

    public function testDefaultMarkdownEmailIsSentAndContainsText()
    {
        Mail::to('michael@mail.com')->send($mailable = new DefaultMarkdownMailable);

        $mailable
            ->assertSeeInText('Welcome to your new Laravel application.')
            ->assertSeeInHtml('Welcome to your new Laravel application.');

        $email = app('mailer')->getSymfonyTransport()->messages()[0]->getOriginalMessage()->toString();

        $this->assertStringContainsString('Welcome to your new Laravel application.', $email);
    }

    public function testEmailRecipientAndSubject()
    {
        Mail::to('michael@mail.com')->send(new DefaultMarkdownMailable);

        $email = app('mailer')->getSymfonyTransport()->messages()[0]->getOriginalMessage()->toString();

        $this->assertStringContainsString('To: michael@mail.com', $email);
        $this->assertStringContainsString('Subject: Subject from Mailable', $email);
    }
}

class DefaultMarkdownMailable extends Mailable
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Subject from Mailable',
        );
    }

    public function content(): Content
    {
        return (new Content)
            ->greeting('Hello, world!')
            ->line('Welcome to your new Laravel application.')
            ->line('My basic content')
            ->salutation('Sincerely, Laravel');
    }
}
