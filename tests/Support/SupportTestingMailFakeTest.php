<?php

namespace Illuminate\Tests\Support;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Testing\Fakes\MailFake;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

class MailFakeTest extends TestCase
{
    public function testAssertSent()
    {
        $fake = new MailFake;
        $mailable = new MailableStub;

        try {
            $fake->assertSent(MailableStub::class);
        } catch(ExpectationFailedException $exception) {
            $this->assertEquals('The expected [Illuminate\Tests\Support\MailableStub] mailable was not sent.
Failed asserting that false is true.', $exception->getMessage());
        }

        $fake->to('taylor@laravel.com')->send($mailable);

        $fake->assertSent(MailableStub::class);
    }

    public function testAssertNotSent()
    {
        $fake = new MailFake;
        $mailable = new MailableStub;

        $fake->assertNotSent(MailableStub::class);

        $fake->to('taylor@laravel.com')->send($mailable);

        try {
            $fake->assertNotSent(MailableStub::class);
        } catch(ExpectationFailedException $exception) {
            $this->assertEquals('The unexpected [Illuminate\Tests\Support\MailableStub] mailable was sent.
Failed asserting that false is true.', $exception->getMessage());
        }
    }

    public function testAssertSentTimes()
    {
        $fake = new MailFake;
        $mailable = new MailableStub;

        $fake->to('taylor@laravel.com')->send($mailable);
        $fake->to('taylor@laravel.com')->send($mailable);

        try {
            $fake->assertSent(MailableStub::class, 1);
        } catch(ExpectationFailedException $exception) {
            $this->assertEquals('The expected [Illuminate\Tests\Support\MailableStub] mailable was sent 2 times instead of 1 times.
Failed asserting that false is true.', $exception->getMessage());
        }

        $fake->assertSent(MailableStub::class, 2);
    }

    public function testAssertNothingSent()
    {
        $fake = new MailFake;
        $mailable = new MailableStub;

        $fake->assertNothingSent();

        $fake->to('taylor@laravel.com')->send($mailable);

        try {
            $fake->assertNothingSent();
        } catch(ExpectationFailedException $exception) {
            $this->assertEquals('Mailables were sent unexpectedly.
Failed asserting that an array is empty.', $exception->getMessage());
        }
    }
}


class MailableStub extends Mailable
{
    public $framework = 'Laravel';

    protected $version = '5.5';

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->with('first_name', 'Taylor')
             ->withLastName('Otwell');
    }
}