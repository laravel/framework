<?php

namespace Illuminate\Tests\Support;

use Illuminate\Contracts\Support\DeferringDisplayableValue;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\EncodedHtmlString;
use Illuminate\Support\HtmlString;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SupportEncodedHtmlStringTest extends TestCase
{
    protected function tearDown(): void
    {
        EncodedHtmlString::flushState();
    }

    public function testBasicHtmlEncoding(): void
    {
        $html = new EncodedHtmlString('<script>alert("xss")</script>');

        $this->assertSame(
            '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;',
            $html->toHtml()
        );
    }

    public function testSpecialCharactersAreEncoded(): void
    {
        $html = new EncodedHtmlString('Tom & Jerry < "Friends" & \'Rivals\'');

        $this->assertSame(
            'Tom &amp; Jerry &lt; &quot;Friends&quot; &amp; &#039;Rivals&#039;',
            $html->toHtml()
        );
    }

    public function testNullValueReturnsEmptyString(): void
    {
        $html = new EncodedHtmlString(null);

        $this->assertSame('', $html->toHtml());
    }

    public function testEmptyStringRemainsEmpty(): void
    {
        $html = new EncodedHtmlString('');

        $this->assertSame('', $html->toHtml());
    }

    public function testIntegerValue(): void
    {
        $html = new EncodedHtmlString(42);

        $this->assertSame('42', $html->toHtml());
    }

    public function testFloatValue(): void
    {
        $html = new EncodedHtmlString(3.14);

        $this->assertSame('3.14', $html->toHtml());
    }

    public function testDoubleEncodeTrue(): void
    {
        $html = new EncodedHtmlString('&amp; already encoded', true);

        $this->assertSame('&amp;amp; already encoded', $html->toHtml());
    }

    public function testDoubleEncodeFalse(): void
    {
        $html = new EncodedHtmlString('&amp; already encoded', false);

        $this->assertSame('&amp; already encoded', $html->toHtml());
    }

    public function testDoubleEncodeDefaultIsTrue(): void
    {
        $html = new EncodedHtmlString('&amp;');

        $this->assertSame('&amp;amp;', $html->toHtml());
    }

    public function testHtmlableValueIsReturnedDirectly(): void
    {
        $inner = new HtmlString('<strong>bold</strong>');
        $html = new EncodedHtmlString($inner);

        $this->assertSame('<strong>bold</strong>', $html->toHtml());
    }

    public function testDeferringDisplayableValueIsResolved(): void
    {
        $deferred = new class implements DeferringDisplayableValue
        {
            public function resolveDisplayableValue()
            {
                return '<em>resolved</em>';
            }
        };

        $html = new EncodedHtmlString($deferred);

        $this->assertSame('&lt;em&gt;resolved&lt;/em&gt;', $html->toHtml());
    }

    public function testDeferringDisplayableValueResolvingToHtmlable(): void
    {
        $deferred = new class implements DeferringDisplayableValue
        {
            public function resolveDisplayableValue()
            {
                return new HtmlString('<em>safe html</em>');
            }
        };

        $html = new EncodedHtmlString($deferred);

        $this->assertSame('<em>safe html</em>', $html->toHtml());
    }

    public function testBackedEnumValue(): void
    {
        $html = new EncodedHtmlString(SupportEncodedHtmlStringTestEnum::Foo);

        $this->assertSame('foo&lt;bar&gt;', $html->toHtml());
    }

    public function testBackedIntEnumValue(): void
    {
        $html = new EncodedHtmlString(SupportEncodedHtmlStringTestIntEnum::One);

        $this->assertSame('1', $html->toHtml());
    }

    public function testEncodeUsingCustomFactory(): void
    {
        EncodedHtmlString::encodeUsing(function ($value, $doubleEncode) {
            return strtoupper($value);
        });

        $html = new EncodedHtmlString('hello');

        $this->assertSame('HELLO', $html->toHtml());
    }

    public function testEncodeUsingReceivesDoubleEncodeParameter(): void
    {
        $receivedDoubleEncode = null;

        EncodedHtmlString::encodeUsing(function ($value, $doubleEncode) use (&$receivedDoubleEncode) {
            $receivedDoubleEncode = $doubleEncode;

            return $value;
        });

        (new EncodedHtmlString('test', false))->toHtml();

        $this->assertFalse($receivedDoubleEncode);
    }

    public function testFlushStateResetsCustomFactory(): void
    {
        EncodedHtmlString::encodeUsing(function ($value, $doubleEncode) {
            return 'custom';
        });

        $this->assertSame('custom', (new EncodedHtmlString('test'))->toHtml());

        EncodedHtmlString::flushState();

        $this->assertSame('test', (new EncodedHtmlString('test'))->toHtml());
    }

    public function testConvertWithQuotes(): void
    {
        $result = EncodedHtmlString::convert('"quotes" & \'apostrophes\'', true);

        $this->assertSame('&quot;quotes&quot; &amp; &#039;apostrophes&#039;', $result);
    }

    public function testConvertWithoutQuotes(): void
    {
        $result = EncodedHtmlString::convert('"quotes" & \'apostrophes\'', false);

        $this->assertSame('"quotes" &amp; \'apostrophes\'', $result);
    }

    public function testConvertWithNullValue(): void
    {
        $result = EncodedHtmlString::convert(null);

        $this->assertSame('', $result);
    }

    public function testConvertDoubleEncode(): void
    {
        $this->assertSame('&amp;amp;', EncodedHtmlString::convert('&amp;', true, true));
        $this->assertSame('&amp;', EncodedHtmlString::convert('&amp;', true, false));
    }

    public function testToStringReturnsSameAsToHtml(): void
    {
        $html = new EncodedHtmlString('<div>content</div>');

        $this->assertSame($html->toHtml(), (string) $html);
    }

    public function testIsEmpty(): void
    {
        $this->assertTrue((new EncodedHtmlString(''))->isEmpty());
        $this->assertTrue((new EncodedHtmlString(null))->isEmpty());
        $this->assertFalse((new EncodedHtmlString('content'))->isEmpty());
    }

    public function testIsNotEmpty(): void
    {
        $this->assertTrue((new EncodedHtmlString('content'))->isNotEmpty());
        $this->assertFalse((new EncodedHtmlString(''))->isNotEmpty());
    }

    public function testConvertHandlesSubstituteCharacters(): void
    {
        // ENT_SUBSTITUTE should replace invalid code unit sequences with U+FFFD
        $result = EncodedHtmlString::convert("invalid \x80 byte");

        $this->assertStringContainsString("\u{FFFD}", $result);
    }

    public function testUtf8MultibyteCharacters(): void
    {
        $html = new EncodedHtmlString('Héllo Wörld <日本語>');

        $this->assertSame('Héllo Wörld &lt;日本語&gt;', $html->toHtml());
    }
}

enum SupportEncodedHtmlStringTestEnum: string
{
    case Foo = 'foo<bar>';
}

enum SupportEncodedHtmlStringTestIntEnum: int
{
    case One = 1;
}

