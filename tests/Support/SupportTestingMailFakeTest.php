<?php

namespace Illuminate\Tests\Support;

use Illuminate\Container\Container;
use Illuminate\Contracts\Mail\Mailable as MailableContract;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Testing\Fakes\MailFake;
use Illuminate\Contracts\View\Factory as FactoryContract;
use Illuminate\View\Factory;
use Illuminate\View\View;
use PHPUnit\Framework\Constraint\ExceptionMessage;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Mockery as m;

class SupportTestingMailFakeTest extends TestCase
{
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
        $this->fake = new MailFake;
        $this->mailable = new MailableStub;
    }

    public function testAssertSent()
    {
        try {
            $this->fake->assertSent(MailableStub::class);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\MailableStub] mailable was not sent.'));
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

    public function testAssertNotSent()
    {
        $this->fake->assertNotSent(MailableStub::class);

        $this->fake->to('taylor@laravel.com')->send($this->mailable);

        try {
            $this->fake->assertNotSent(MailableStub::class);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The unexpected [Illuminate\Tests\Support\MailableStub] mailable was sent.'));
        }
    }

    public function testAssertSentTimes()
    {
        $this->fake->to('taylor@laravel.com')->send($this->mailable);
        $this->fake->to('taylor@laravel.com')->send($this->mailable);

        try {
            $this->fake->assertSent(MailableStub::class, 1);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\MailableStub] mailable was sent 2 times instead of 1 times.'));
        }

        $this->fake->assertSent(MailableStub::class, 2);
    }

    public function testAssertQueued()
    {
        try {
            $this->fake->assertQueued(MailableStub::class);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\MailableStub] mailable was not queued.'));
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
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\MailableStub] mailable was queued 2 times instead of 1 times.'));
        }

        $this->fake->assertQueued(MailableStub::class, 2);
    }

    public function testSendQueuesAMailableThatShouldBeQueued()
    {
        $this->fake->to('taylor@laravel.com')->send(new QueueableMailableStub);

        $this->fake->assertQueued(QueueableMailableStub::class);

        try {
            $this->fake->assertSent(QueueableMailableStub::class);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\QueueableMailableStub] mailable was not sent.'));
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
            $this->assertThat($e, new ExceptionMessage('The following mailables were sent unexpectedly: Illuminate\Tests\Support\MailableStub'));
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
            $this->assertThat($e, new ExceptionMessage('The following mailables were queued unexpectedly: Illuminate\Tests\Support\MailableStub'));
        }
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

    public function testRender()
    {
        $viewFactory = m::mock(Factory::class);
        $view = m::mock(View::class);
        $viewFactory->shouldReceive('make')->twice()->andReturn($view);
        $view->shouldReceive('render')->once()->andReturn('Hello Taylor HTML');
        $view->shouldReceive('render')->once()->andReturn('Hello Taylor TEXT');

        $this->fake->to('taylor@laravel.com')->send(new MailableWithContentsStub);

        Container::getInstance()->instance('mailer', $this->fake);
        Container::getInstance()->instance(FactoryContract::class, $viewFactory);

        $this->fake->assertSent(function (MailableWithContentsStub $mail) {
            $rendered = $mail->render();
            $this->assertStringContainsString('Taylor HTML', $rendered['html']);
            $this->assertStringContainsString('Taylor TEXT', $rendered['text']);
            return true;
        });
    }

    public function testRenderMarkdown()
    {
        $viewFactory = m::mock(Factory::class);
        $view = m::mock(View::class);
        $viewFactory->shouldReceive('make')->once()->andReturn($view);
        $viewFactory->shouldReceive('flushFinderCache')->once();
        $viewFactory->shouldReceive('replaceNamespace')->once()->andReturn($viewFactory);
        $viewFactory->shouldReceive('exists')->once()->andReturn(false);
        $view->shouldReceive('render')->once()->andReturn('Hello Taylor');

        $this->fake->to('taylor@laravel.com')->send(new MarkdownMailableStub);

        Container::getInstance()->instance('mailer', $this->fake);
        Container::getInstance()->instance(FactoryContract::class, $viewFactory);

        $this->fake->assertSent(function (MarkdownMailableStub $mail) {
            $rendered = $mail->render();
            $this->assertStringContainsString('Taylor</p>', $rendered['html']);
            $this->assertStringContainsString('Taylor', $rendered['text']);
            $this->assertStringNotContainsString('Taylor</p>', $rendered['text']);
            return true;
        });
    }
}

class MailableStub extends Mailable implements MailableContract
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

class MailableWithContentsStub extends Mailable implements MailableContract
{
    public $framework = 'Laravel';

    protected $version = '6.0';

    public $name = 'Taylor';

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->view('emails.html.hello')
            ->text('emails.plain.hello');
    }
}

class MarkdownMailableStub extends Mailable implements MailableContract
{
    public $framework = 'Laravel';

    protected $version = '6.0';

    public $name = 'Taylor';

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->markdown('emails.markdown.hello');
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
