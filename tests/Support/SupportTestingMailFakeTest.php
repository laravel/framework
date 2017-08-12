<?php

namespace Illuminate\Tests\Support;

use Illuminate\Mail\Mailable;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\Testing\Fakes\MailFake;
use PHPUnit\Framework\ExpectationFailedException;

class MailFakeTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->fake = new MailFake;
        $this->mailable = new MailableStub;
    }

    public function testAssertSent()
    {
        try {
            $this->fake->assertSent(MailableStub::class);
        } catch (ExpectationFailedException $exception) {
            $this->assertEquals('The expected [Illuminate\Tests\Support\MailableStub] mailable was not sent.
Failed asserting that false is true.', $exception->getMessage());
        }

        $this->fake->to('taylor@laravel.com')->send($this->mailable);

        $this->fake->assertSent(MailableStub::class);
    }

    public function testAssertNotSent()
    {
        $this->fake->assertNotSent(MailableStub::class);

        $this->fake->to('taylor@laravel.com')->send($this->mailable);

        try {
            $this->fake->assertNotSent(MailableStub::class);
        } catch (ExpectationFailedException $exception) {
            $this->assertEquals('The unexpected [Illuminate\Tests\Support\MailableStub] mailable was sent.
Failed asserting that false is true.', $exception->getMessage());
        }
    }

    public function testAssertSentTimes()
    {
        $this->fake->to('taylor@laravel.com')->send($this->mailable);
        $this->fake->to('taylor@laravel.com')->send($this->mailable);

        try {
            $this->fake->assertSent(MailableStub::class, 1);
        } catch (ExpectationFailedException $exception) {
            $this->assertEquals('The expected [Illuminate\Tests\Support\MailableStub] mailable was sent 2 times instead of 1 times.
Failed asserting that false is true.', $exception->getMessage());
        }

        $this->fake->assertSent(MailableStub::class, 2);
    }

    public function testAssertQueued()
    {
        try {
            $this->fake->assertQueued(MailableStub::class);
        } catch (ExpectationFailedException $exception) {
            $this->assertEquals('The expected [Illuminate\Tests\Support\MailableStub] mailable was not queued.
Failed asserting that false is true.', $exception->getMessage());
        }

        $this->fake->to('taylor@laravel.com')->queue($this->mailable);

        $this->fake->assertQueued(MailableStub::class);
    }

    public function testAssertQueuedTimes()
    {
        $this->fake->to('taylor@laravel.com')->queue($this->mailable);
        $this->fake->to('taylor@laravel.com')->queue($this->mailable);

        try {
            $this->fake->assertQueued(MailableStub::class, 1);
        } catch (ExpectationFailedException $exception) {
            $this->assertEquals('The expected [Illuminate\Tests\Support\MailableStub] mailable was queued 2 times instead of 1 times.
Failed asserting that false is true.', $exception->getMessage());
        }

        $this->fake->assertQueued(MailableStub::class, 2);
    }

    public function testAssertNothingSent()
    {
        $this->fake->assertNothingSent();

        $this->fake->to('taylor@laravel.com')->send($this->mailable);

        try {
            $this->fake->assertNothingSent();
        } catch (ExpectationFailedException $exception) {
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
