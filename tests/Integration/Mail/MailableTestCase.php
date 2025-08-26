<?php

namespace Illuminate\Tests\Integration\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

abstract class MailableTestCase extends TestCase
{
    /** {@inheritdoc} */
    #[\Override]
    protected function defineEnvironment($app)
    {
        $app['view']->addLocation(__DIR__.'/Fixtures');
    }

    #[DataProvider('markdownEncodedDataProvider')]
    public function testItCanAssertMarkdownEncodedString($given, $expected)
    {
        $mailable = new class($given) extends Mailable
        {
            public function __construct(public string $message)
            {
                //
            }

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

        $mailable->assertSeeInHtml($expected, false);
    }

    public static function markdownEncodedDataProvider()
    {
        yield ['[Laravel](https://laravel.com)', 'My message is: [Laravel](https://laravel.com)'];

        yield [
            '![Welcome to Laravel](https://laravel.com/assets/img/welcome/background.svg)',
            'My message is: ![Welcome to Laravel](https://laravel.com/assets/img/welcome/background.svg)',
        ];

        yield [
            'Visit https://laravel.com/docs to browse the documentation',
            'My message is: Visit https://laravel.com/docs to browse the documentation',
        ];

        yield [
            'Visit <https://laravel.com/docs> to browse the documentation',
            'My message is: Visit &lt;https://laravel.com/docs&gt; to browse the documentation',
        ];

        yield [
            'Visit <span>https://laravel.com/docs</span> to browse the documentation',
            'My message is: Visit &lt;span&gt;https://laravel.com/docs&lt;/span&gt; to browse the documentation',
        ];
    }
}
