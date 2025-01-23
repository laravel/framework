<?php

namespace Illuminate\Tests\Support;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\OnConnection;
use Illuminate\Foundation\Queue\OnQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;

class SupportMailTest extends TestCase
{
    public function testItRegisterAndCallMacros()
    {
        Mail::macro('test', fn (string $str) => $str === 'foo'
            ? 'it works!'
            : 'it failed.',
        );

        $this->assertEquals('it works!', Mail::test('foo'));
    }

    public function testItRegisterAndCallMacrosWhenFaked()
    {
        Mail::macro('test', fn (string $str) => $str === 'foo'
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

    #[DataProvider('queueDataProvider')]
    public function testQueuedMailableRespectsOnQueueAttribute(string $mailableClass, string $queueName)
    {
        Queue::fake();

        Mail::send(new $mailableClass());

        Queue::assertPushedOn(
            $queueName,
            SendQueuedMailable::class,
            fn ($job) => is_a($job->mailable, $mailableClass, true)
        );
    }

    #[DataProvider('queueDataProvider')]
    public function testQueuedMailableDispatchedLaterRespectsOnQueueAttribute(string $mailableClass, string $queueName)
    {
        Queue::fake();

        Mail::later(100, new $mailableClass);

        Queue::assertPushedOn(
            $queueName,
            SendQueuedMailable::class,
            fn ($job) => is_a($job->mailable, $mailableClass, true)
        );
    }

    #[DataProvider('connectionDataProvider')]
    public function testQueuedMailableRespectsOnConnectionAttribute(string $mailableClass, string $connectionName)
    {
        Queue::fake();

        Queue::before(function (JobProcessing $jobProcessing) use ($connectionName) {
            $this->assertEquals($connectionName, $jobProcessing->connectionName);
        });

        Mail::send(new $mailableClass());

    }

    #[DataProvider('connectionDataProvider')]
    public function testLaterQueuedMailableRespectsOnConnectionAttribute(string $mailableClass, string $connectionName)
    {
        Queue::fake();

        Queue::before(function (JobProcessing $jobProcessing) use ($connectionName) {
            $this->assertEquals($connectionName, $jobProcessing->connectionName);
        });

        Mail::later(100, new $mailableClass());
    }

    public function testQueueableMailableUsesQueueAndConnectionFromClassProperties()
    {
        Queue::fake();
        Mail::send(new TestMailWithOnQueueAndOnConnectionSetAndBothPropertiesSet());

        Queue::assertPushed(function (SendQueuedMailable $sendQueuedMailable) {
            return $sendQueuedMailable->mailable instanceof TestMailWithOnQueueAndOnConnectionSetAndBothPropertiesSet
                && $sendQueuedMailable->connection === 'my-connection'
                && $sendQueuedMailable->queue === 'some-other-queue';
        });
    }

    public function testQueueableMailableDispatchedLaterUsesQueueAndConnectionFromClassProperties()
    {
        Queue::fake();
        Mail::later(100, new TestMailWithOnQueueAndOnConnectionSetAndBothPropertiesSet());

        Queue::assertPushed(function (SendQueuedMailable $sendQueuedMailable) {
            return $sendQueuedMailable->mailable instanceof TestMailWithOnQueueAndOnConnectionSetAndBothPropertiesSet
                && $sendQueuedMailable->connection === 'my-connection'
                && $sendQueuedMailable->queue === 'some-other-queue';
        });
    }

    /**
     * @return array<string, array{class-string<Mailable>, string}>
     */
    public static function queueDataProvider(): array
    {
        return [
            'string for queue' => [TestMailWithOnQueue::class, 'my-queue'],
            'enum for queue' => [TestMailWithEnumOnQueue::class, 'queue-from-enum'],
        ];
    }

    public static function connectionDataProvider(): array
    {
        return [
            'string for connection' => [TestMailWithOnConnection::class, 'connection-string'],
            'enum for connection' => [TestMailWithEnumOnConnection::class, 'connection-from-enum'],
        ];
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

#[OnConnection('connection-string')]
class TestMailWithOnConnection extends Mailable implements ShouldQueue
{
    public $queue = 'queue';

    public function build()
    {
        return $this->view('view');
    }
}

#[OnConnection(SupportMailTestEnum::Connection)]
class TestMailWithEnumOnConnection extends Mailable implements ShouldQueue
{
    public $queue = 'queue';

    public function build()
    {
        return $this->view('view');
    }
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
