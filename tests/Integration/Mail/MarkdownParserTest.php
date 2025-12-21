<?php

namespace Illuminate\Tests\Integration\Mail;

use Illuminate\Mail\Markdown;
use Illuminate\Support\EncodedHtmlString;
use Illuminate\Support\HtmlString;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class MarkdownParserTest extends TestCase
{
    /** {@inheritdoc} */
    #[\Override]
    protected function tearDown(): void
    {
        Markdown::flushState();
        EncodedHtmlString::flushState();

        parent::tearDown();
    }

    #[DataProvider('markdownDataProvider')]
    public function testItCanParseMarkdownString($given, $expected)
    {
        tap(Markdown::parse($given), function ($html) use ($expected) {
            $this->assertInstanceOf(HtmlString::class, $html);

            $this->assertStringEqualsStringIgnoringLineEndings($expected.PHP_EOL, (string) $html);
            $this->assertSame((string) $html, (string) $html->toHtml());
        });
    }

    #[DataProvider('markdownEncodedDataProvider')]
    public function testItCanParseMarkdownEncodedString($given, $expected)
    {
        tap(Markdown::parse($given, encoded: true), function ($html) use ($expected) {
            $this->assertInstanceOf(HtmlString::class, $html);

            $this->assertStringEqualsStringIgnoringLineEndings($expected.PHP_EOL, (string) $html);
        });
    }

    public static function markdownDataProvider()
    {
        yield ['[Laravel](https://laravel.com)', '<p><a href="https://laravel.com">Laravel</a></p>'];
        yield ['\[Laravel](https://laravel.com)', '<p>[Laravel](https://laravel.com)</p>'];
        yield ['![Welcome to Laravel](https://laravel.com/assets/img/welcome/background.svg)', '<p><img src="https://laravel.com/assets/img/welcome/background.svg" alt="Welcome to Laravel" /></p>'];
        yield ['!\[Welcome to Laravel](https://laravel.com/assets/img/welcome/background.svg)', '<p>![Welcome to Laravel](https://laravel.com/assets/img/welcome/background.svg)</p>'];
        yield ['Visit https://laravel.com/docs to browse the documentation', '<p>Visit https://laravel.com/docs to browse the documentation</p>'];
        yield ['Visit <https://laravel.com/docs> to browse the documentation', '<p>Visit <a href="https://laravel.com/docs">https://laravel.com/docs</a> to browse the documentation</p>'];
        yield ['Visit <span>https://laravel.com/docs</span> to browse the documentation', '<p>Visit <span>https://laravel.com/docs</span> to browse the documentation</p>'];
    }

    public static function markdownEncodedDataProvider()
    {
        yield [new EncodedHtmlString('[Laravel](https://laravel.com)'), '<p>[Laravel](https://laravel.com)</p>'];

        yield [
            new EncodedHtmlString('![Welcome to Laravel](https://laravel.com/assets/img/welcome/background.svg)'),
            '<p>![Welcome to Laravel](https://laravel.com/assets/img/welcome/background.svg)</p>',
        ];

        yield [
            new EncodedHtmlString('Visit https://laravel.com/docs to browse the documentation'),
            '<p>Visit https://laravel.com/docs to browse the documentation</p>',
        ];

        yield [
            new EncodedHtmlString('Visit <https://laravel.com/docs> to browse the documentation'),
            '<p>Visit &lt;https://laravel.com/docs&gt; to browse the documentation</p>',
        ];

        yield [
            new EncodedHtmlString('Visit <span>https://laravel.com/docs</span> to browse the documentation'),
            '<p>Visit &lt;span&gt;https://laravel.com/docs&lt;/span&gt; to browse the documentation</p>',
        ];

        yield [
            new EncodedHtmlString(new HtmlString('Visit <span>https://laravel.com/docs</span> to browse the documentation')),
            '<p>Visit <span>https://laravel.com/docs</span> to browse the documentation</p>',
        ];

        yield [
            '![Welcome to Laravel](https://laravel.com/assets/img/welcome/background.svg)<br />'.new EncodedHtmlString('Visit <span>https://laravel.com/docs</span> to browse the documentation'),
            '<p><img src="https://laravel.com/assets/img/welcome/background.svg" alt="Welcome to Laravel" /><br />Visit &lt;span&gt;https://laravel.com/docs&lt;/span&gt; to browse the documentation</p>',
        ];
    }
}
