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

    /**
     * @expectedException PHPUnit\Framework\ExpectationFailedException
     * @expectedExceptionMessage The expected [Illuminate\Tests\Support\MailableStub] mailable was not sent.
     */
    public function testAssertSent()
    {
        $this->fake->assertSent(MailableStub::class);

        $this->fake->to('taylor@laravel.com')->send($this->mailable);

        $this->fake->assertSent(MailableStub::class);
    }

    /**
     * @expectedException PHPUnit\Framework\ExpectationFailedException
     * @expectedExceptionMessage The unexpected [Illuminate\Tests\Support\MailableStub] mailable was sent.
     */
    public function testAssertNotSent()
    {
        $this->fake->assertNotSent(MailableStub::class);

        $this->fake->to('taylor@laravel.com')->send($this->mailable);

        $this->fake->assertNotSent(MailableStub::class);
    }

    /**
     * @expectedException PHPUnit\Framework\ExpectationFailedException
     * @expectedExceptionMessage The expected [Illuminate\Tests\Support\MailableStub] mailable was sent 2 times instead of 1 times.
     */
    public function testAssertSentTimes()
    {
        $this->fake->to('taylor@laravel.com')->send($this->mailable);
        $this->fake->to('taylor@laravel.com')->send($this->mailable);

        $this->fake->assertSent(MailableStub::class, 1);
        $this->fake->assertSent(MailableStub::class, 2);
    }

    /**
     * @expectedException PHPUnit\Framework\ExpectationFailedException
     * @expectedExceptionMessage The expected [Illuminate\Tests\Support\MailableStub] mailable was not queued.
     */
    public function testAssertQueued()
    {
        $this->fake->assertQueued(MailableStub::class);

        $this->fake->to('taylor@laravel.com')->queue($this->mailable);

        $this->fake->assertQueued(MailableStub::class);
    }

    /**
     * @expectedException PHPUnit\Framework\ExpectationFailedException
     * @expectedExceptionMessage The expected [Illuminate\Tests\Support\MailableStub] mailable was queued 2 times instead of 1 times.
     */
    public function testAssertQueuedTimes()
    {
        $this->fake->to('taylor@laravel.com')->queue($this->mailable);
        $this->fake->to('taylor@laravel.com')->queue($this->mailable);

        $this->fake->assertQueued(MailableStub::class, 1);
        $this->fake->assertQueued(MailableStub::class, 2);
    }

    /**
     * @expectedException PHPUnit\Framework\ExpectationFailedException
     * @expectedExceptionMessage The expected [Illuminate\Tests\Support\QueueableMailableStub] mailable was not sent.
     */
    public function testSendQueuesAMailableThatShouldBeQueued()
    {
        $this->fake->to('taylor@laravel.com')->send(new QueueableMailableStub);

        $this->fake->assertSent(QueueableMailableStub::class);
        $this->fake->assertQueued(QueueableMailableStub::class);
    }

    /**
     * @expectedException PHPUnit\Framework\ExpectationFailedException
     * @expectedExceptionMessage Mailables were sent unexpectedly.
     */
    public function testAssertNothingSent()
    {
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
