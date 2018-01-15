<?php

namespace Illuminate\Tests\Support;

use Illuminate\Mail\Mailable;
use PHPUnit\Framework\TestCase;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Testing\Fakes\MailFake;

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
        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected [Illuminate\\Tests\\Support\\MailableStub] mailable was not sent.');

        $this->fake->assertSent(MailableStub::class);

        $this->fake->to('taylor@laravel.com')->send($this->mailable);

        $this->fake->assertSent(MailableStub::class);
    }

    public function testAssertNotSent()
    {
        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);
        $this->expectExceptionMessage('The unexpected [Illuminate\\Tests\\Support\\MailableStub] mailable was sent.');

        $this->fake->assertNotSent(MailableStub::class);

        $this->fake->to('taylor@laravel.com')->send($this->mailable);

        $this->fake->assertNotSent(MailableStub::class);
    }

    public function testAssertSentTimes()
    {
        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected [Illuminate\\Tests\\Support\\MailableStub] mailable was sent 2 times instead of 1 times.');

        $this->fake->to('taylor@laravel.com')->send($this->mailable);
        $this->fake->to('taylor@laravel.com')->send($this->mailable);

        $this->fake->assertSent(MailableStub::class, 1);
        $this->fake->assertSent(MailableStub::class, 2);
    }

    public function testAssertQueued()
    {
        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected [Illuminate\\Tests\\Support\\MailableStub] mailable was not queued.');

        $this->fake->assertQueued(MailableStub::class);

        $this->fake->to('taylor@laravel.com')->queue($this->mailable);

        $this->fake->assertQueued(MailableStub::class);
    }

    public function testAssertQueuedTimes()
    {
        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected [Illuminate\\Tests\\Support\\MailableStub] mailable was queued 2 times instead of 1 times.');

        $this->fake->to('taylor@laravel.com')->queue($this->mailable);
        $this->fake->to('taylor@laravel.com')->queue($this->mailable);

        $this->fake->assertQueued(MailableStub::class, 1);
        $this->fake->assertQueued(MailableStub::class, 2);
    }

    public function testSendQueuesAMailableThatShouldBeQueued()
    {
        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected [Illuminate\\Tests\\Support\\QueueableMailableStub] mailable was not sent.');

        $this->fake->to('taylor@laravel.com')->send(new QueueableMailableStub);

        $this->fake->assertSent(QueueableMailableStub::class);
        $this->fake->assertQueued(QueueableMailableStub::class);
    }

    public function testAssertNothingSent()
    {
        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);
        $this->expectExceptionMessage('Mailables were sent unexpectedly.');

        $this->fake->assertNothingSent();

        $this->fake->to('taylor@laravel.com')->send($this->mailable);

        $this->fake->assertNothingSent();
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

class QueueableMailableStub extends Mailable implements ShouldQueue
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
