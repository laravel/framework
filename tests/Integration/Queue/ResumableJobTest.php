<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\ExecutionContext\ExecutionState;
use Illuminate\Contracts\Queue\Resumable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\ResumableTrait;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Orchestra\Testbench\Attributes\WithMigration;
use Psr\Log\LoggerInterface;

#[WithMigration]
#[WithMigration('cache')]
#[WithMigration('queue')]
class ResumableJobTest extends QueueTestCase
{
    protected function defineEnvironment($app)
    {

        $app['config']->set('cache.default', 'database');
        $app['config']->set('queue.default', 'database');
        parent::defineEnvironment($app);
    }

    protected function setUp(): void
    {
        parent::setUp();
        TestResumableJob::flush();
    }

    public function test_job_sync_queued()
    {
        $this->travelTo('2025-06-29T00:00:01.000Z');
        Mail::fake();
        Event::fake();
        TestResumableJob::dispatchSync(1234);

        $this->assertSame([
            'getData' => 1,
            'updateDatabase' => 1,
            'sendEmail' => 1,
        ], TestResumableJob::$timesCalled);

        Mail::assertSentCount(2);
        Event::assertDispatched('emails_sent', function ($_, $emailsSent) {
            $this->assertEquals([9876, 1002], $emailsSent);

            return true;
        });
    }

    public function test_job_queued()
    {
        $this->travelTo('2025-06-29T00:00:01.000Z');
        Mail::fake();
        Event::fake();
        TestResumableJob::dispatch(1234);
        $this->runQueueWorkerCommand(['--once' => true]);
        $executionState = Cache::get('execution:1');

        $this->assertInstanceof(ExecutionState::class, $executionState);
        $this->assertTrue($executionState->hasCompletedStep('get_data'));
        $this->assertTrue($executionState->hasCompletedStep('update_database'));
        $this->assertTrue($executionState->hasCompletedStep('send_email'));
    }

    public function test_resumes_with_job_failure()
    {
        $this->travelTo('2025-06-29T00:00:01.000Z');
        Mail::fake();
        Event::fake();
        TestResumableJob::dispatch(1234);
        Cache::put('throw_exception', true);
        $this->runQueueWorkerCommand(['--once' => true]);
        $executionState = Cache::get('execution:1');

        $this->assertInstanceof(ExecutionState::class, $executionState);
        $this->assertTrue($executionState->hasCompletedStep('get_data'));
        $this->assertFalse($executionState->hasCompletedStep('update_database'));
        $this->assertFalse($executionState->hasCompletedStep('send_email'));
        $this->assertNull(Cache::get('throw_exception'));

        $this->runQueueWorkerCommand(['--once' => true]);
        //dd(DB::table('cache')->get());
        $executionState = Cache::get('execution:2');
        $this->assertInstanceof(ExecutionState::class, $executionState);

        $this->assertTrue($executionState->hasCompletedStep('get_data'));
        $this->assertTrue($executionState->hasCompletedStep('update_database'));
        $this->assertTrue($executionState->hasCompletedStep('send_email'));
    }
}

class TestResumableJob implements Resumable, ShouldQueue
{
    use InteractsWithQueue;
    use ResumableTrait;
    use Dispatchable;

    public static bool $throwException = false;
    public static array $timesCalled = [];

    public static function flush(): void
    {
        static::$timesCalled = [];
    }

    public int $tries = 2;

    public function __construct(
        public int $userId
    ) {
    }

    public function handle(LoggerInterface $logger)
    {
        $response = $this->step('get_data', $this->getData(...));
        $logger->info('Data received', $response);
        $this->step('update_database', $this->updateDatabase(...));

        $emailsSent = $this->step('send_email', $this->sendEmail(...));

        event('emails_sent', $emailsSent);
    }

    private function getData()
    {
        Cache::increment('getData');
        self::$timesCalled['getData'] ??= 0;
        self::$timesCalled['getData']++;

        return [
            'data' => [
                [
                    'id' => 9876,
                    'email' => 'taylor@laravel.com',
                ],
                [
                    'id' => 1002,
                    'email' => 'abby@laravel.com',
                ],
            ],
        ];
    }

    private function updateDatabase()
    {
        self::$timesCalled['updateDatabase'] ??= 0;
        self::$timesCalled['updateDatabase']++;
        Cache::increment('updateDatabase');
        if (Cache::get('throw_exception')) {
            Cache::forget('throw_exception');
            throw new \Exception('You asked me to throw this');
        }
    }

    private function sendEmail(): array
    {
        Cache::increment('sendEmail');
        self::$timesCalled['sendEmail'] ??= 0;
        self::$timesCalled['sendEmail']++;
        $recipients = [];

        $users = $this->context->getState()->resultFor('get_data')['data'];
        foreach($users as $user) {
            Mail::to($user['email'])->send((new Mailable)->subject('test email: '.$this->userId));
            $recipients[] = $user['id'];
        }

        return $recipients;
    }
}
