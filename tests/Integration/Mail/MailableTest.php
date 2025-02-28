<?php

namespace Illuminate\Tests\Integration\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Orchestra\Testbench\TestCase;

class MailableTest extends TestCase
{
    /** {@inheritdoc} */
    #[\Override]
    protected function defineEnvironment($app)
    {
        $app['view']->addLocation(__DIR__.'/Fixtures');
    }

    public function testItCanAssertMarkdownEncodedString()
    {
        $mailable = new class extends Mailable {
            public $message = "<script ' &";

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
        };

        $mailable
            ->assertSeeInText("My message is: <script ' &.")
            ->assertSeeInHtml("My message is: <script ' &.");
    }
}
