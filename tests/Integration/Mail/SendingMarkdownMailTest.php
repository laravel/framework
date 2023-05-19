<?php

namespace Illuminate\Tests\Integration\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Orchestra\Testbench\TestCase;

class SendingMarkdownMailTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('mail.driver', 'array');

        View::addLocation(__DIR__.'/Fixtures');
    }

    public function testMailIsSent()
    {
        $mailable = new Markdown();

        $mailable
            ->assertHasSubject('My title')
            ->assertSeeInOrderInHtml(['Hello World', 'Click me', 'Thanks,']);
    }

    public function testEmbeddedData()
    {
        Mail::to('test@mail.com')->locale('ar')->send(new Markdown());

        $email = app('mailer')->getSymfonyTransport()->messages()[0]->getOriginalMessage()->toString();

        $this->assertStringContainsString('Content-Disposition: inline; name=logo.svg; filename=logo.svg', $email);
    }
}

class Markdown extends Mailable
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
