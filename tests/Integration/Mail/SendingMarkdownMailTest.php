<?php

namespace Illuminate\Tests\Integration\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Markdown;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Stringable;
use Orchestra\Testbench\TestCase;

class SendingMarkdownMailTest extends TestCase
{
    protected function defineEnvironment($app)
    {
        $app['config']->set('mail.driver', 'array');

        $app['view']->addNamespace('mail', __DIR__.'/Fixtures')
            ->addLocation(__DIR__.'/Fixtures');
    }

    public function testMailIsSent()
    {
        $mailable = new BasicMailable();

        $mailable
            ->assertHasSubject('My basic title')
            ->assertSeeInText('My basic content')
            ->assertSeeInHtml('My basic content');
    }

    public function testMailMayHaveSpecificTextView()
    {
        $mailable = new BasicMailableWithTextView();

        $mailable
            ->assertHasSubject('My basic title')
            ->assertSeeInHtml('My basic content')
            ->assertSeeInText('My basic text view')
            ->assertDontSeeInText('My basic content');
    }

    public function testEmbed()
    {
        Mail::to('test@mail.com')->send($mailable = new EmbedMailable());

        $mailable->assertSeeInHtml('Embed content: cid:');
        $mailable->assertSeeInText('Embed content: ');
        $mailable->assertDontSeeInText('Embed content: cid:');

        $email = app('mailer')->getSymfonyTransport()->messages()[0]->getOriginalMessage()->toString();

        $cid = explode(' cid:', (new Stringable($email))->explode("\r\n")
            ->filter(fn ($line) => str_contains($line, ' content: cid:'))
            ->first())[1];

        $filename = explode('Embed file: ', (new Stringable($email))->explode("\r\n")
            ->filter(fn ($line) => str_contains($line, ' file:'))
            ->first())[1];

        $this->assertStringContainsString(<<<EOT
        Content-Type: application/x-php; name=$filename\r
        Content-Transfer-Encoding: base64\r
        Content-Disposition: inline; name=$filename;\r
         filename=$filename\r
        Content-ID: <$cid>\r
        EOT, $email);
    }

    public function testEmbedData()
    {
        Mail::to('test@mail.com')->send($mailable = new EmbedDataMailable());

        $mailable->assertSeeInText('Embed data content: ');
        $mailable->assertSeeInHtml('Embed data content: cid:');

        $email = app('mailer')->getSymfonyTransport()->messages()[0]->getOriginalMessage()->toString();

        $this->assertStringContainsString(<<<EOT
        Content-Type: image/png; name=foo.jpg\r
        Content-Transfer-Encoding: base64\r
        Content-Disposition: inline; name=foo.jpg; filename=foo.jpg\r
        EOT, $email);
    }

    public function testEmbedMultilineImage()
    {
        Mail::to('test@mail.com')->send($mailable = new EmbedMultilineMailable());

        $html = html_entity_decode($mailable->render());

        $this->assertStringContainsString('Embed multiline content: <img', $html);
        $this->assertStringContainsString('alt="multiline image"', $html);
        $this->assertStringContainsString('<img src="cid:', $html);
    }

    public function testMessageAsPublicPropertyMayBeDefinedAsViewData()
    {
        Mail::to('test@mail.com')->send($mailable = new MessageAsPublicPropertyMailable());

        $mailable
            ->assertSeeInText('My message is: My message.')
            ->assertSeeInHtml('My message is: My message.');

        $email = app('mailer')->getSymfonyTransport()->messages()[0]->getOriginalMessage()->toString();

        $this->assertStringContainsString('My message is: My message.', $email);
    }

    public function testMessageAsWithNamedParameterMayBeDefinedAsViewData()
    {
        Mail::to('test@mail.com')->send($mailable = new MessageAsWithNamedParameterMailable());

        $mailable
            ->assertSeeInText('My message is: My message.')
            ->assertSeeInHtml('My message is: My message.');

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

    public function testEmbeddedImageContentIdConsistencyAcrossMailerFailoverClones()
    {
        Mail::to('test@mail.com')->send($mailable = new EmbedImageMailable);

        /** @var \Symfony\Component\Mime\Email $originalEmail */
        $originalEmail = app('mailer')->getSymfonyTransport()->messages()[0]->getOriginalMessage();
        $expectedContentId = $originalEmail->getAttachments()[0]->getContentId();

        // Simulate failover mailer scenario where email is cloned for retry.
        // After shallow clone, the CID in HTML and attachment Content-ID header should remain consistent.
        $firstClonedEmail = quoted_printable_decode((clone $originalEmail)->toString());
        [$htmlCid, $attachmentContentId] = $this->extractContentIdsFromEmail($firstClonedEmail);

        $this->assertEquals($htmlCid, $attachmentContentId, 'HTML img src CID should match attachment Content-ID header');
        $this->assertEquals($expectedContentId, $htmlCid, 'Cloned email CID should match original attachment CID');

        // Verify consistency is maintained across multiple clone operations (e.g., multiple retries).
        $secondClonedEmail = quoted_printable_decode((clone $originalEmail)->toString());
        [$htmlCid, $attachmentContentId] = $this->extractContentIdsFromEmail($secondClonedEmail);

        $this->assertEquals($htmlCid, $attachmentContentId, 'HTML img src CID should match attachment Content-ID header on subsequent clone');
        $this->assertEquals($expectedContentId, $htmlCid, 'Multiple clones should preserve original CID');
    }

    /**
     * Extract Content IDs from email for embedded image validation.
     *
     * @param  string  $rawEmail
     * @return array{0: string|null, 1: string|null} [HTML image CID, attachment Content-ID]
     */
    private function extractContentIdsFromEmail(string $rawEmail): array
    {
        // Extract CID from HTML <img src="cid:..."> tag.
        preg_match('/<img[^>]+src="cid:([^"]+)"/', $rawEmail, $htmlMatches);
        $htmlImageCid = $htmlMatches[1] ?? null;

        // Extract CID from MIME attachment Content-ID header.
        preg_match('/Content-ID:\s*<([^>]+)>/', $rawEmail, $headerMatches);
        $attachmentContentId = $headerMatches[1] ?? null;

        return [$htmlImageCid, $attachmentContentId];
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

class BasicMailableWithTextView extends Mailable
{
    public $textView = 'text';

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

class EmbedMultilineMailable extends Mailable
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
            markdown: 'embed-multiline',
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

class EmbedImageMailable extends Mailable
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
            markdown: 'embed-image',
            with: [
                'image' => __DIR__.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.'empty_image.jpg',
            ]
        );
    }
}

class MessageAsPublicPropertyMailable extends Mailable
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

class MessageAsWithNamedParameterMailable extends Mailable
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
            markdown: 'message',
            with: [
                'message' => 'My message',
            ]
        );
    }
}
