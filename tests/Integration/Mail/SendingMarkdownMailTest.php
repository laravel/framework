<?php

namespace Illuminate\Tests\Integration\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Markdown;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Orchestra\Testbench\TestCase;

class SendingMarkdownMailTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('mail.driver', 'array');

        View::addNamespace('mail', __DIR__.'/Fixtures');
        View::addLocation(__DIR__.'/Fixtures');
    }

    public function testMailIsSent()
    {
        $mailable = new MarkdownMailable();

        $mailable
            ->assertHasSubject('My title')
            ->assertSeeInOrderInHtml(['Hello World', 'Click me', 'Thanks,']);
    }

    public function testEmbeddedData()
    {
        Mail::to('test@mail.com')->send(new MarkdownMailable());

        $email = app('mailer')->getSymfonyTransport()->messages()[0]->getOriginalMessage()->toString();

        $this->assertStringContainsString('Content-Disposition: inline; name=logo.svg; filename=logo.svg', $email);
    }

    public function testTheme()
    {
        Mail::to('test@mail.com')->send(new MarkdownMailable());
        $this->assertSame('default', app(Markdown::class)->getTheme());

        Mail::to('test@mail.com')->send(new MarkdownMailableWithTheme());
        $this->assertSame('taylor', app(Markdown::class)->getTheme());

        Mail::to('test@mail.com')->send(new MarkdownMailable());
        $this->assertSame('default', app(Markdown::class)->getTheme());
    }
}

class MarkdownMailable extends Mailable
{
    public function envelope()
    {
        return new Envelope(
            subject: 'My title',
        );
    }

    public function content()
    {
        return new Content(
            markdown: 'markdown',
        );
    }
}

class MarkdownMailableWithTheme extends Mailable
{
    public $theme = 'taylor';

    public function envelope()
    {
        return new Envelope(
            subject: 'My title',
        );
    }

    public function content()
    {
        return new Content(
            markdown: 'markdown',
        );
    }
}
