<?php

namespace Illuminate\Tests\Mail;

use Closure;
use Illuminate\Support\Facades\Mail;
use Orchestra\Testbench\TestCase;

class MailMiddlewareTest extends TestCase
{
    public function testMailableIsSentWhenMiddlewarePasses()
    {
        Mail::fake();

        Mail::to('jeff@test.com')
            ->through(PassingMailMiddleware::class)
            ->send(new Mailable);

        Mail::assertSent(Mailable::class);
    }

    public function testMailableIsQueuedWhenMiddlewarePasses()
    {
        Mail::fake();

        Mail::to('jeff@test.com')
            ->through(PassingMailMiddleware::class)
            ->queue(new Mailable);

        Mail::assertQueued(Mailable::class);
    }

    public function testMailableIsNotSentWhenMiddlewareFails()
    {
        Mail::fake();

        Mail::to('jeff@test.com')
            ->through(FailingMailMiddleware::class)
            ->send(new Mailable);

        Mail::assertNotSent(Mailable::class);
    }

    public function testMailableIsNotQueuedWhenMiddlewareFails()
    {
        Mail::fake();

        Mail::to('jeff@test.com')
            ->through(FailingMailMiddleware::class)
            ->queue(new Mailable);

        Mail::assertNotQueued(Mailable::class);
    }

    public function testMailableIsNotSentWhenOneOfTwoMiddlewareFails()
    {
        Mail::fake();

        Mail::to('jeff@test.com')
            ->through([PassingMailMiddleware::class, FailingMailMiddleware::class])
            ->send(new Mailable);

        Mail::assertNotSent(Mailable::class);
    }

    public function testMailableIsNotQueuedWhenOneOfTwoMiddlewareFails()
    {
        Mail::fake();

        Mail::to('jeff@test.com')
            ->through([PassingMailMiddleware::class, FailingMailMiddleware::class])
            ->queue(new Mailable);

        Mail::assertNotQueued(Mailable::class);
    }

    public function testMailableWithInvalidToIsNotSent()
    {
        Mail::fake();

        Mail::to('')
            ->through(EnsureRecipientIsValid::class)
            ->send(new Mailable);

        Mail::assertNotSent(Mailable::class);
    }

    public function testMailableWithSomeInvalidRecipientsCanBeModifiedInMiddleware()
    {
        Mail::fake();

        Mail::to(['', 'jeff@test.com'])
            ->through(EnsureRecipientIsValid::class)
            ->send(new Mailable);

        Mail::assertSent(Mailable::class, 1);
    }
}

class EnsureRecipientIsValid
{
    public function handle(Mailable $mailable, Closure $next)
    {
        $mailable->to = collect($mailable->to)
            ->filter(function ($recipient) {
                return filter_var($recipient['address'], FILTER_VALIDATE_EMAIL) !== false;
            })
            ->values()
            ->all();

        return ! empty($mailable->to) && $next($mailable);
    }
}

class PassingMailMiddleware
{
    public function handle(Mailable $mailable, Closure $next)
    {
        return $next($mailable);
    }
}

class FailingMailMiddleware
{
    public function handle(Mailable $mailable, Closure $next)
    {
        //
    }
}

class Mailable extends \Illuminate\Mail\Mailable
{
    public function build()
    {
        return $this->html('Hello');
    }
}
