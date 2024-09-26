<?php

namespace Illuminate\Tests\Mail;

use Illuminate\Mail\Mailable;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

class MailMailableAssertionsTest extends TestCase
{
    public function testMailableAssertSeeInTextPassesWhenPresent()
    {
        $mailable = new MailableAssertionsStub;

        $mailable->assertSeeInText('First Item');
    }

    public function testMailableAssertSeeInTextFailsWhenAbsent()
    {
        $mailable = new MailableAssertionsStub;

        $this->expectException(AssertionFailedError::class);

        $mailable->assertSeeInText('Fourth Item');
    }

    public function testMailableAssertDontSeeInTextPassesWhenAbsent()
    {
        $mailable = new MailableAssertionsStub;

        $mailable->assertDontSeeInText('Fourth Item');
    }

    public function testMailableAssertDontSeeInTextFailsWhenPresent()
    {
        $mailable = new MailableAssertionsStub;

        $this->expectException(AssertionFailedError::class);

        $mailable->assertDontSeeInText('First Item');
    }

    public function testMailableAssertSeeInHtmlPassesWhenPresent()
    {
        $mailable = new MailableAssertionsStub;

        $mailable->assertSeeInHtml('Fourth & Fifth Item');

        $mailable->assertSeeInHtml('<li>First Item</li>', false);
    }

    public function testMailableAssertSeeInHtmlFailsWhenAbsent()
    {
        $mailable = new MailableAssertionsStub;

        $this->expectException(AssertionFailedError::class);

        $mailable->assertSeeInHtml('<li>Fourth Item</li>');
    }

    public function testMailableAssertDontSeeInHtmlPassesWhenAbsent()
    {
        $mailable = new MailableAssertionsStub;

        $mailable->assertDontSeeInHtml('<li>Fourth Item</li>');
    }

    public function testMailableAssertDontSeeInHtmlEscapedFailsWhenPresent()
    {
        $mailable = new MailableAssertionsStub;

        $this->expectException(AssertionFailedError::class);

        $mailable->assertDontSeeInHtml('Fourth & Fifth Item');
    }

    public function testMailableAssertDontSeeInHtmlUnescapedFailsWhenPresent()
    {
        $mailable = new MailableAssertionsStub;

        $this->expectException(AssertionFailedError::class);

        $mailable->assertDontSeeInHtml('<li>First Item</li>', false);
    }

    public function testMailableAssertSeeInOrderTextPassesWhenPresentInOrder()
    {
        $mailable = new MailableAssertionsStub;

        $mailable->assertSeeInOrderInText([
            'First Item',
            'Second Item',
            'Third Item',
        ]);
    }

    public function testMailableAssertSeeInOrderTextFailsWhenAbsentInOrder()
    {
        $mailable = new MailableAssertionsStub;

        $this->expectException(AssertionFailedError::class);

        $mailable->assertSeeInOrderInText([
            'First Item',
            'Third Item',
            'Second Item',
        ]);
    }

    public function testMailableAssertInOrderHtmlPassesWhenPresentInOrder()
    {
        $mailable = new MailableAssertionsStub;

        $mailable->assertSeeInOrderInHtml([
            'Third Item',
            'Fourth & Fifth Item',
            'Sixth Item',
        ]);

        $mailable->assertSeeInOrderInHtml([
            '<li>First Item</li>',
            '<li>Second Item</li>',
            '<li>Third Item</li>',
        ], false);
    }

    public function testMailableAssertInOrderHtmlFailsWhenAbsentInOrder()
    {
        $mailable = new MailableAssertionsStub;

        $this->expectException(AssertionFailedError::class);

        $mailable->assertSeeInOrderInHtml([
            '<li>Second Item</li>',
            '<li>First Item</li>',
            '<li>Third Item</li>',
        ]);
    }

    public function testMailableAssertSeeInTextWithApostrophePassesWhenPresent()
    {
        $mailable = new MailableAssertionsStub;

        $mailable->assertSeeInText("It's a wonderful day");
    }

    public function testMailableAssertSeeInTextWithApostropheFailsWhenAbsent()
    {
        $mailable = new MailableAssertionsStub;

        $this->expectException(AssertionFailedError::class);

        $mailable->assertSeeInText("It's not a wonderful day");
    }

    public function testMailableAssertDontSeeInTextWithApostrophePassesWhenAbsent()
    {
        $mailable = new MailableAssertionsStub;

        $mailable->assertDontSeeInText("It's not a wonderful day");
    }

    public function testMailableAssertDontSeeInTextWithApostropheFailsWhenPresent()
    {
        $mailable = new MailableAssertionsStub;

        $this->expectException(AssertionFailedError::class);

        $mailable->assertDontSeeInText("It's a wonderful day");
    }

    public function testMailableAssertSeeInHtmlWithApostropheFailsWhenAbsent()
    {
        $mailable = new MailableAssertionsStub;

        $this->expectException(AssertionFailedError::class);

        $mailable->assertSeeInHtml("<li>It's not a wonderful day</li>");
    }

    public function testMailableAssertDontSeeInHtmlWithApostrophePassesWhenAbsent()
    {
        $mailable = new MailableAssertionsStub;

        $mailable->assertDontSeeInHtml("<li>It's not a wonderful day</li>");
    }

    public function testMailableAssertDontSeeInHtmlWithApostropheFailsWhenPresent()
    {
        $mailable = new MailableAssertionsStub;

        $this->expectException(AssertionFailedError::class);

        $mailable->assertDontSeeInHtml("<li>It's a wonderful day</li>", false);
    }

    public function testMailableAssertSeeInOrderInHtmlWithApostrophePassesWhenPresentInOrder()
    {
        $mailable = new MailableAssertionsStub;

        $mailable->assertSeeInOrderInHtml([
            'First Item',
            'Sixth Item',
            'It\'s a wonderful day',
        ]);

        $mailable->assertSeeInOrderInHtml([
            '<li>First Item</li>',
            '<li>It\'s a wonderful day</li>',
        ], false);
    }

    public function testMailableAssertSeeInOrderInHtmlWithApostropheFailsWhenAbsentInOrder()
    {
        $mailable = new MailableAssertionsStub;

        $this->expectException(AssertionFailedError::class);

        $mailable->assertSeeInOrderInHtml([
            'It\'s a wonderful day',
            'First Item',
            'Sixth Item',
        ]);
    }
}

class MailableAssertionsStub extends Mailable
{
    protected function renderForAssertions()
    {
        $text = <<<'EOD'
        # List
        - First Item
        - Second Item
        - Third Item
        - Fourth & Fifth Item
        - Sixth Item
        - It's a wonderful day
        EOD;

        $html = <<<'EOD'
        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml">
        <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        </style>
        </head>
        <body>
        <h1>List</h1>
        <ul>
        <li>First Item</li>
        <li>Second Item</li>
        <li>Third Item</li>
        <li>Fourth &amp; Fifth Item</li>
        <li>Sixth Item</li>
        <li>It's a wonderful day</li>
        </ul>
        </body>
        </html>
        EOD;

        return [$html, $text];
    }
}
