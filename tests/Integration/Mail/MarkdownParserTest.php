<?php

namespace Illuminate\Tests\Integration\Mail;

use Illuminate\Contracts\Support\DeferringDisplayableValue;
use Illuminate\Mail\Markdown;
use Illuminate\Mail\MarkdownString;
use Illuminate\Support\HtmlString;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class MarkdownParserTest extends TestCase
{
    #[DataProvider('markdownDataProvider')]
    public function testItCanParseMarkdownString($given, $expected)
    {
        tap(Markdown::parse($given), function ($html) use ($expected) {
            $this->assertInstanceOf(MarkdownString::class, $html);
            $this->assertInstanceOf(HtmlString::class, $html);
            $this->assertInstanceOf(DeferringDisplayableValue::class, $html);

            $this->assertSame($expected.PHP_EOL, (string) $html);
            $this->assertSame((string) $html, $html->toHtml());
        });
    }

    #[DataProvider('markdownEncodedDataProvider')]
    public function testItCanParseMarkdownEncodedString($given, $expected)
    {
        tap(Markdown::parse($given), function ($html) use ($expected) {
            $this->assertInstanceOf(MarkdownString::class, $html);
            $this->assertInstanceOf(HtmlString::class, $html);
            $this->assertInstanceOf(DeferringDisplayableValue::class, $html);

            $this->assertSame($expected.PHP_EOL, e($html));
        });
    }

    public static function markdownDataProvider()
    {
        yield ['[Laravel](https://laravel.com)', '<p><a href="https://laravel.com">Laravel</a></p>'];
        yield ['\[Laravel](https://laravel.com)', '<p>[Laravel](https://laravel.com)</p>'];
        yield ['![Welcome to Laravel](https://laravel.com/assets/img/welcome/background.svg)', '<p><img src="https://laravel.com/assets/img/welcome/background.svg" alt="Welcome to Laravel" /></p>'];
        yield ['!\[Welcome to Laravel](https://laravel.com/assets/img/welcome/background.svg)', '<p>![Welcome to Laravel](https://laravel.com/assets/img/welcome/background.svg)</p>'];
        yield ['Visit https://laravel.com/docs to browse the documentation', '<p>Visit https://laravel.com/docs to browse the documentation</p>'];
    }

    public static function markdownEncodedDataProvider()
    {
        yield ['[Laravel](https://laravel.com)', '&lt;p&gt;[Laravel](https://laravel.com)&lt;/p&gt;'];
        yield ['![Welcome to Laravel](https://laravel.com/assets/img/welcome/background.svg)', '&lt;p&gt;![Welcome to Laravel](https://laravel.com/assets/img/welcome/background.svg)&lt;/p&gt;'];
        yield ['Visit https://laravel.com/docs to browse the documentation', '&lt;p&gt;Visit https://laravel.com/docs to browse the documentation&lt;/p&gt;'];
    }
}
