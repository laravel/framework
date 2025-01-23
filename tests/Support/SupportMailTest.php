<?php

namespace Illuminate\Tests\Support;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\OnConnection;
use Illuminate\Foundation\Queue\OnQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\TestWith;

class SupportMailTest extends TestCase
{
    public function testItRegisterAndCallMacros()
    {
        Mail::macro('test', fn(string $str) => $str === 'foo'
            ? 'it works!'
            : 'it failed.',
        );

        $this->assertEquals('it works!', Mail::test('foo'));
    }

    public function testItRegisterAndCallMacrosWhenFaked()
    {
        Mail::macro('test', fn(string $str) => $str === 'foo'
            ? 'it works!'
            : 'it failed.',
        );

        Mail::fake();

        $this->assertEquals('it works!', Mail::test('foo'));
    }

    public function testEmailSent()
    {
        Mail::fake();
        Mail::assertNothingSent();

        Mail::to('hello@laravel.com')->send(new TestMail());

        Mail::assertSent(TestMail::class);
    }

    #[TestWith([TestMailWithOnQueue::class, 'my-queue'], 'string for queue')]
    #[TestWith([TestMailWithEnumOnQueue::class, 'queue-from-enum'], 'enum for queue')]
    public function testQueuedMailableRespectsStringOnQueueAttribute(string $mailableClass, string $queueName)
    {
        Queue::fake();

        Mail::send(new $mailableClass());

        Queue::assertPushedOn(
            $queueName,
            SendQueuedMailable::class,
            fn ($job) => is_a($job->mailable, $mailableClass, true)
        );
    }

    public function testQueueableMailableUsesQueueIfSetAsProperty()
    {
        Queue::fake();
        Mail::send(new TestMailWithOnQueueAndOnConnectionSetAndBothPropertiesSet());

        Queue::assertPushed(function(SendQueuedMailable $sendQueuedMailable) {
            return $sendQueuedMailable->mailable instanceof TestMailWithOnQueueAndOnConnectionSetAndBothPropertiesSet
                && $sendQueuedMailable->connection === 'my-connection'
                && $sendQueuedMailable->queue === 'some-other-queue';
        });
    }
}

class TestMail extends Mailable
{
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('view');
    }
}

#[OnQueue('my-queue')]
class TestMailWithOnQueue extends Mailable implements ShouldQueue
{
}

#[OnQueue(SupportMailTestEnum::Queue)]
class TestMailWithEnumOnQueue extends Mailable implements ShouldQueue
{
}

#[OnQueue(SupportMailTestEnum::Queue)]
#[OnConnection(SupportMailTestEnum::Connection)]
class TestMailWithOnQueueAndOnConnectionSetAndBothPropertiesSet extends Mailable implements ShouldQueue
{
    public $queue = 'some-other-queue';
    public $connection = 'my-connection';
}

enum SupportMailTestEnum: string
{
    case Queue = 'queue-from-enum';
    case Connection = 'connection-from-enum';
}
