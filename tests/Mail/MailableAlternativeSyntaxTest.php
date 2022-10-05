<?php

namespace Illuminate\Tests\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class MailableAlternativeSyntaxTest extends TestCase
{
    public function testBasicMailableInspection()
    {
        $mailable = new MailableWithAlternativeSyntax;

        $this->assertTrue($mailable->hasTo('taylor@laravel.com'));
        $this->assertTrue($mailable->hasCc('adam@laravel.com'));
        $this->assertTrue($mailable->hasBcc('tyler@laravel.com'));
        $this->assertTrue($mailable->hasTo('taylor@laravel.com', 'Taylor Otwell'));
        $this->assertFalse($mailable->hasTo('taylor@laravel.com', 'Wrong Name'));

        $mailable->to(new Address('abigail@laravel.com', 'Abigail Otwell'));
        $this->assertTrue($mailable->hasTo('taylor@laravel.com', 'Taylor Otwell'));

        $this->assertTrue($mailable->hasSubject('Test Subject'));
        $this->assertFalse($mailable->hasSubject('Wrong Subject'));
        $this->assertTrue($mailable->hasTag('tag-1'));
        $this->assertTrue($mailable->hasMetadata('test-meta', 'test-meta-value'));

        $reflection = new ReflectionClass($mailable);
        $reflection->getMethod('prepareMailableForDelivery')->setAccessible(true);

        $reflection->getMethod('prepareMailableForDelivery')->invoke($mailable);

        $this->assertEquals('test-view', $mailable->view);
        $this->assertEquals(['test-data-key' => 'test-data-value'], $mailable->viewData);
        $this->assertEquals(2, count($mailable->to));
        $this->assertEquals(1, count($mailable->cc));
        $this->assertEquals(1, count($mailable->bcc));
    }
}

class MailableWithAlternativeSyntax extends Mailable
{
    public function envelope()
    {
        return new Envelope(
            to: [new Address('taylor@laravel.com', 'Taylor Otwell')],
            cc: [new Address('adam@laravel.com', 'Adam Wathan')],
            bcc: [new Address('tyler@laravel.com', 'Tyler Blair')],
            subject: 'Test Subject',
            tags: ['tag-1', 'tag-2'],
            metadata: ['test-meta' => 'test-meta-value'],
        );
    }

    public function content()
    {
        return new Content(
            view: 'test-view',
            with: ['test-data-key' => 'test-data-value'],
        );
    }
}
