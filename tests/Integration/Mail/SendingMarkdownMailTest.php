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
        $mailable = new BasicMailable();

        $mailable
            ->assertHasSubject('My basic title')
            ->assertSeeInText('My basic content')
            ->assertSeeInHtml('My basic content');
    }

    public function testEmbed()
    {
        Mail::to('test@mail.com')->send($mailable = new EmbedMailable());

        $mailable->assertSeeInHtml('Embed content: cid:');
        $mailable->assertSeeInText('Embed content: ');
        $mailable->assertDontSeeInText('Embed content: cid:');

        $email = app('mailer')->getSymfonyTransport()->messages()[0]->getOriginalMessage()->toString();

        $cid = explode('cid:', str($email)->explode("\r\n")
            ->filter(fn ($line) => str_starts_with($line, '<html><body><p>Embed content: cid:'))

            ->first())[1];

        $cid = substr($cid, 0, -1);

        $this->assertStringContainsString("Content-Type: application/x-php; name=$cid", $email);
        $this->assertStringContainsString('Content-Transfer-Encoding: base64', $email);
        $this->assertStringContainsString("Content-Disposition: inline; name=$cid", $email);
    }

    public function testEmbedData()
    {
        Mail::to('test@mail.com')->send($mailable = new EmbedDataMailable());

        $mailable->assertSeeInHtml('Embed data content: cid:foo.jpg');
        $mailable->assertSeeInText('Embed data content: ');
        $mailable->assertDontSeeInText('Embed data content: cid:foo.jpg');

        $email = app('mailer')->getSymfonyTransport()->messages()[0]->getOriginalMessage()->toString();

        $this->assertStringContainsString('Content-Type: image/png; name=foo.jpg', $email);
        $this->assertStringContainsString('Content-Transfer-Encoding: base64', $email);
        $this->assertStringContainsString('Content-Disposition: inline; name=foo.jpg; filename=foo.jpg', $email);
    }

    public function testMessageMayBeDefinedAsViewData()
    {
        Mail::to('test@mail.com')->send(new MessageMailable());

        $email = app('mailer')->getSymfonyTransport()->messages()[0]->getOriginalMessage()->toString();

        $this->assertStringContainsString('My message is: My message.', $email);
    }

    public function testTheme()
    {
        Mail::to('test@mail.com')->send(new BasicMailable());
        $this->assertSame('default', app(Markdown::class)->getTheme());

        Mail::to('test@mail.com')->send(new BasicMailableWithTheme());
        $this->assertSame('taylor', app(Markdown::class)->getTheme());

        Mail::to('test@mail.com')->send(new BasicMailable());
        $this->assertSame('default', app(Markdown::class)->getTheme());
    }
}

class BasicMailable extends Mailable
{
    public function envelope()
    {
        return new Envelope(
            subject: 'My basic title',
        );
    }

    public function content()
    {
        return new Content(
            markdown: 'basic',
        );
    }
}

class BasicMailableWithTheme extends Mailable
{
    public $theme = 'taylor';

    public function envelope()
    {
        return new Envelope(
            subject: 'My basic title',
        );
    }

    public function content()
    {
        return new Content(
            markdown: 'basic',
        );
    }
}

class EmbedMailable extends Mailable
{
    public function envelope()
    {
        return new Envelope(
            subject: 'My basic title',
        );
    }

    public function content()
    {
        return new Content(
            markdown: 'embed',
        );
    }
}

class EmbedDataMailable extends Mailable
{
    public function envelope()
    {
        return new Envelope(
            subject: 'My basic title',
        );
    }

    public function content()
    {
        return new Content(
            markdown: 'embed-data',
        );
    }
}

class MessageMailable extends Mailable
{
    public $message = 'My message';

    public function envelope()
    {
        return new Envelope(
            subject: 'My basic title',
        );
    }

    public function content()
    {
        return new Content(
            markdown: 'message',
        );
    }
}
