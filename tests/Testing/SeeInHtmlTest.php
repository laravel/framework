<?php

namespace Illuminate\Tests\Testing;

use Illuminate\Testing\Constraints\SeeInHtml;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SeeInHtmlTest extends TestCase
{
    public function testCollapsesUnicodeWhitespaceFromHtmlEntities()
    {
        $constraint = new SeeInHtml('Hello World');

        $this->assertTrue($constraint->matches(['<p>Hello&nbsp;World</p>']));
        $this->assertTrue($constraint->matches(['<p>Hello&#8195;World</p>']));
        $this->assertTrue($constraint->matches(['<p>Hello&#12288;World</p>']));
    }

    #[DataProvider('unicodeWhitespaceCharacters')]
    public function testCollapsesRawUnicodeWhitespace(string $whitespace)
    {
        $constraint = new SeeInHtml('Hello World');

        $this->assertTrue($constraint->matches(["<p>Hello{$whitespace}World</p>"]));
    }

    public static function unicodeWhitespaceCharacters(): array
    {
        return [
            'no-break space (U+00A0)' => ["\u{00A0}"],
            'en space (U+2002)' => ["\u{2002}"],
            'em space (U+2003)' => ["\u{2003}"],
            'thin space (U+2009)' => ["\u{2009}"],
            'ideographic space (U+3000)' => ["\u{3000}"],
        ];
    }

    public function testCollapsesMultipleAsciiWhitespace()
    {
        $constraint = new SeeInHtml('Hello World');

        $this->assertTrue($constraint->matches(["<p>Hello   World</p>"]));
        $this->assertTrue($constraint->matches(["<p>Hello\tWorld</p>"]));
        $this->assertTrue($constraint->matches(["<p>Hello\nWorld</p>"]));
        $this->assertTrue($constraint->matches(["<p>Hello \t\n World</p>"]));
    }

    public function testFailsWhenValueIsAbsent()
    {
        $constraint = new SeeInHtml('Hello World');

        $this->assertFalse($constraint->matches(['<p>Goodbye World</p>']));
    }

    public function testNegateInvertsTheAssertion()
    {
        $constraint = new SeeInHtml('Hello World', ordered: false, negate: true);

        $this->assertTrue($constraint->matches(['<p>Goodbye World</p>']));
        $this->assertFalse($constraint->matches(['<p>Hello&nbsp;World</p>']));
    }

    public function testOrderedRespectsSequenceAcrossUnicodeWhitespace()
    {
        $constraint = new SeeInHtml('Hello&nbsp;beautiful&#8195;World', ordered: true);

        $this->assertTrue($constraint->matches(['Hello', 'beautiful', 'World']));
        $this->assertFalse($constraint->matches(['World', 'Hello']));
    }
}
