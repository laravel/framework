<?php

namespace Illuminate\Tests\Integration\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Tests\App\Mail\MailableWithQueue;
use Illuminate\Tests\App\Mail\RateLimitedMailable;
use Orchestra\Testbench\TestCase;

class SendingQueuedMailTest extends TestCase
{
    protected function defineEnvironment($app)
    {
        $app['config']->set('mail.driver', 'array');

        $app['view']->addLocation(__DIR__.'/Fixtures');
    }

    public function testMailIsSentWithDefaultLocale()
    {
        Queue::fake();

        Mail::to('test@mail.com')->queue(new RateLimitedMailable);

        Queue::assertPushed(SendQueuedMailable::class, function ($job) {
            return $job->middleware[0] instanceof RateLimited;
        });
    }

    public function testMailIsSentWhenRoutingQueue()
    {
        Queue::fake();

        Queue::route(Mailable::class, 'mail-queue', 'mail-connection');

        Mail::to('test@mail.com')->queue(new RateLimitedMailable);

        Queue::connection('mail-connection')->assertPushedOn('mail-queue', SendQueuedMailable::class);
    }

    public function testMailIsSentWhenForwardingQueue()
    {
        Queue::fake();

        Queue::forward('mail-queue', 'main', 'mail-connection');

        Mail::to('test@mail.com')->queue(new MailableWithQueue);

        Queue::connection('mail-connection')->assertPushedOn('mail-queue', SendQueuedMailable::class);
    }

    public function testMailIsSentWithDelay()
    {
        Queue::fake();

        $delay = Carbon::now()->addMinutes(10);

        Mail::to('test@mail.com')->later($delay, new RateLimitedMailable);

        Queue::assertPushed(SendQueuedMailable::class, function ($job) use ($delay) {
            return $job->delay === $delay;
        });
    }
}
