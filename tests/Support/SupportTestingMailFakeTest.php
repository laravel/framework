<?php

namespace Illuminate\Tests\Support;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Testing\Fakes\MailFake;
use Mockery as m;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

class SupportTestingMailFakeTest extends TestCase
{
    /**
     * @var \Mockery
     */
    private $mailManager;

    /**
     * @var \Illuminate\Support\Testing\Fakes\MailFake
     */
    private $fake;

    /**
     * @var \Illuminate\Tests\Support\MailableStub
     */
    private $mailable;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mailManager = m::mock(MailManager::class);
        $this->fake = new MailFake($this->mailManager);
        $this->mailable = new MailableStub;
    }

    public function testAssertSent()
    {
        try {
            $this->fake->assertSent(MailableStub::class);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString('The expected [Illuminate\Tests\Support\MailableStub] mailable was not sent.', $e->getMessage());
        }

        $this->fake->to('taylor@laravel.com')->send($this->mailable);

        $this->fake->assertSent(MailableStub::class);
    }

    public function testAssertSentWhenRecipientHasPreferredLocale()
    {
        $user = new LocalizedRecipientStub;

        $this->fake->to($user)->send($this->mailable);

        $this->fake->assertSent(MailableStub::class, function ($mail) use ($user) {
            return $mail->hasTo($user) && $mail->locale === 'au';
        });
    }

    public function testAssertTo()
    {
        $this->fake->to('taylor@laravel.com')->send($this->mailable);

        $this->fake->assertSent(MailableStub::class, function ($mail) {
            return $mail->hasTo('taylor@laravel.com');
        });
    }

    public function testAssertCc()
    {
        $this->fake->cc('taylor@laravel.com')->send($this->mailable);

        $this->fake->assertSent(MailableStub::class, function ($mail) {
            return $mail->hasCc('taylor@laravel.com');
        });
    }

    public function testAssertBcc()
    {
        $this->fake->bcc('taylor@laravel.com')->send($this->mailable);

        $this->fake->assertSent(MailableStub::class, function ($mail) {
            return $mail->hasBcc('taylor@laravel.com');
        });
    }

    public function testAssertNotSent()
    {
        $this->fake->assertNotSent(MailableStub::class);

        $this->fake->to('taylor@laravel.com')->send($this->mailable);

        try {
            $this->fake->assertNotSent(MailableStub::class);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString('The unexpected [Illuminate\Tests\Support\MailableStub] mailable was sent.', $e->getMessage());
        }
    }

    public function testAssertNotSentWithClosure()
    {
        $callback = function (MailableStub $mail) {
            return $mail->hasTo('taylor@laravel.com');
        };

        $this->fake->assertNotSent($callback);

        $this->fake->to('taylor@laravel.com')->send($this->mailable);

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessageMatches('/The unexpected \['.preg_quote(MailableStub::class, '/').'\] mailable was sent./m');

        $this->fake->assertNotSent($callback);
    }

    public function testAssertSentTimes()
    {
        $this->fake->to('taylor@laravel.com')->send($this->mailable);
        $this->fake->to('taylor@laravel.com')->send($this->mailable);

        try {
            $this->fake->assertSent(MailableStub::class, 1);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString('The expected [Illuminate\Tests\Support\MailableStub] mailable was sent 2 times instead of 1 times.', $e->getMessage());
        }

        $this->fake->assertSent(MailableStub::class, 2);
    }

    public function testAssertSentCount()
    {
        $this->fake->to('taylor@laravel.com')->send($this->mailable);
        $this->fake->to('taylor@laravel.com')->send($this->mailable);

        try {
            $this->fake->assertSentCount(1);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString('The total number of mailables sent was 2 instead of 1.', $e->getMessage());
        }

        $this->fake->assertSentCount(2);
    }

    public function testAssertQueued()
    {
        try {
            $this->fake->assertQueued(MailableStub::class);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString('The expected [Illuminate\Tests\Support\MailableStub] mailable was not queued.', $e->getMessage());
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
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString('The expected [Illuminate\Tests\Support\MailableStub] mailable was queued 2 times instead of 1 times.', $e->getMessage());
        }

        $this->fake->assertQueued(MailableStub::class, 2);
    }

    public function testAssertQueuedCount()
    {
        $this->fake->to('taylor@laravel.com')->queue($this->mailable);
        $this->fake->to('taylor@laravel.com')->queue($this->mailable);

        try {
            $this->fake->assertQueuedCount(1);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString('The total number of mailables queued was 2 instead of 1.', $e->getMessage());
        }

        $this->fake->assertQueuedCount(2);
    }

    public function testSendQueuesAMailableThatShouldBeQueued()
    {
        $this->fake->to('taylor@laravel.com')->send(new QueueableMailableStub);

        $this->fake->assertQueued(QueueableMailableStub::class);

        try {
            $this->fake->assertSent(QueueableMailableStub::class);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString('The expected [Illuminate\Tests\Support\QueueableMailableStub] mailable was not sent.', $e->getMessage());
        }
    }

    public function testAssertNothingSent()
    {
        $this->fake->assertNothingSent();

        $this->fake->to('taylor@laravel.com')->send($this->mailable);

        try {
            $this->fake->assertNothingSent();
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString('The following mailables were sent unexpectedly: Illuminate\Tests\Support\MailableStub', $e->getMessage());
        }
    }

    public function testAssertNothingQueued()
    {
        $this->fake->assertNothingQueued();

        $this->fake->to('taylor@laravel.com')->queue($this->mailable);

        try {
            $this->fake->assertNothingQueued();
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString('The following mailables were queued unexpectedly: Illuminate\Tests\Support\MailableStub', $e->getMessage());
        }
    }

    public function testAssertOutgoingCount()
    {
        $this->fake->assertNothingOutgoing();

        $this->fake->to('taylor@laravel.com')->queue($this->mailable);

        try {
            $this->fake->assertOutgoingCount(2);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString('The total number of outgoing mailables was 1 instead of 2.', $e->getMessage());
        }

        $this->fake->to('taylor@laravel.com')->send($this->mailable);

        $this->fake->assertOutgoingCount(2);
    }

    public function testAssertQueuedWithClosure()
    {
        $this->fake->to($user = new LocalizedRecipientStub)->queue($this->mailable);

        $this->fake->assertQueued(function (MailableStub $mail) use ($user) {
            return $mail->hasTo($user);
        });
    }

    public function testAssertSentWithClosure()
    {
        $this->fake->to($user = new LocalizedRecipientStub)->send($this->mailable);

        $this->fake->assertSent(function (MailableStub $mail) use ($user) {
            return $mail->hasTo($user);
        });
    }

    public function testMissingMethodsAreForwarded()
    {
        $this->mailManager->shouldReceive('foo')->andReturn('bar');

        $this->assertEquals('bar', $this->fake->foo());
    }
}

class MailableStub extends Mailable
{
    public $framework = 'Laravel';

    protected $version = '6.0';

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

    protected $version = '6.0';

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

class LocalizedRecipientStub implements HasLocalePreference
{
    public $email = 'taylor@laravel.com';

    public function preferredLocale()
    {
        return 'au';
    }
}
