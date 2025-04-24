<?php

namespace Illuminate\Tests\Integration\Mail;

use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Markdown;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\Factories\UserFactory;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class MailableWithoutSecuredEncodingTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** {@inheritdoc} */
    #[\Override]
    protected function defineEnvironment($app)
    {
        $app['view']->addLocation(__DIR__.'/Fixtures');

        Markdown::withoutSecuredEncoding();
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

    #[WithMigration]
    #[DataProvider('markdownEncodedTemplateDataProvider')]
    public function testItCanAssertMarkdownEncodedStringUsingTemplate($given, $expected)
    {
        $user = UserFactory::new()->create([
            'name' => $given,
        ]);

        $mailable = new class($user) extends Mailable
        {
            public $theme = 'taylor';

            public function __construct(public User $user)
            {
                //
            }

            public function build()
            {
                return $this->markdown('message-with-template');
            }
        };

        $mailable->assertSeeInHtml($expected, false);
    }

    public static function markdownEncodedTemplateDataProvider()
    {
        yield ['[Laravel](https://laravel.com)', '<p><em>Hi</em> <a href="https://laravel.com">Laravel</a></p>'];

        yield [
            '![Welcome to Laravel](https://laravel.com/assets/img/welcome/background.svg)',
            '<p><em>Hi</em> <img src="https://laravel.com/assets/img/welcome/background.svg" alt="Welcome to Laravel"></p>',
        ];

        yield [
            'Visit https://laravel.com/docs to browse the documentation',
            '<em>Hi</em> Visit https://laravel.com/docs to browse the documentation',
        ];

        yield [
            'Visit <https://laravel.com/docs> to browse the documentation',
            '<em>Hi</em> Visit &lt;https://laravel.com/docs&gt; to browse the documentation',
        ];

        yield [
            'Visit <span>https://laravel.com/docs</span> to browse the documentation',
            '<em>Hi</em> Visit &lt;span&gt;https://laravel.com/docs&lt;/span&gt; to browse the documentation',
        ];
    }
}
