<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\ExecutionContext\ExecutionState;
use Illuminate\Contracts\Queue\Resumable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\ResumableTrait;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
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
        Cache::put('getData', 0);
        Cache::put('updateDatabase', 0);
        Cache::put('sendEmail', 0);
    }

    public function test_job_sync_queued()
    {
        Mail::fake();
        Event::fake();
        TestResumableJob::dispatchSync(1234);

        $this->assertEquals(1, Cache::get('getData'));
        $this->assertEquals(1, Cache::get('updateDatabase'));
        $this->assertEquals(1, Cache::get('sendEmail'));

        Mail::assertSentCount(2);
        Event::assertDispatched('emails_sent', function ($_, $emailsSent) {
            $this->assertEquals([9876, 1002], $emailsSent);

            return true;
        });
    }

    public function test_job_queued()
    {
        $uuid = Str::freezeUuids();
        Mail::fake();
        Event::fake();
        TestResumableJob::dispatch(1234);
        $this->runQueueWorkerCommand(['--once' => true]);
        $executionState = Cache::get('execution:'.$uuid);

        $this->assertInstanceof(ExecutionState::class, $executionState);
        $this->assertTrue($executionState->hasCompletedStep('get_data'));
        $this->assertTrue($executionState->hasCompletedStep('update_database'));
        $this->assertTrue($executionState->hasCompletedStep('send_email'));
    }

    public function test_job_writes_completed_at_to_execution_state()
    {
        $uuid = Str::freezeUuids();
        $this->travelTo('2025-06-29T00:00:01.000Z');
        Mail::fake();
        Event::fake();

        TestResumableJob::dispatch(1234);
        $this->runQueueWorkerCommand(['--once' => true]);
        $executionState = Cache::get('execution:'.$uuid);

        $this->assertInstanceof(ExecutionState::class, $executionState);
        $this->assertSame([
            'get_data' => $now = Carbon::now()->getTimestamp(),
            'update_database' => $now,
            'send_email' => $now,
        ], (new Collection($executionState->all()))->mapWithKeys(function ($value, $key) {
                return [$key => $value['completed_at']];
            })->all()
        );
    }

    public function test_resumes_with_job_failure()
    {
        $uuid = Str::freezeUuids();
        Mail::fake();
        Event::fake();
        TestResumableJob::dispatch(1234);
        Cache::put('throw_exception', true);
        $this->runQueueWorkerCommand(['--once' => true]);
        $executionState = Cache::get('execution:'.$uuid);

        $this->assertInstanceof(ExecutionState::class, $executionState);
        $this->assertTrue($executionState->hasCompletedStep('get_data'));
        $this->assertFalse($executionState->hasCompletedStep('update_database'));
        $this->assertFalse($executionState->hasCompletedStep('send_email'));
        $this->assertNull(Cache::get('throw_exception'));

        $this->runQueueWorkerCommand(['--once' => true]);
        $executionState = Cache::get('execution:'.$uuid);
        $this->assertInstanceof(ExecutionState::class, $executionState);

        $this->assertTrue($executionState->hasCompletedStep('get_data'));
        $this->assertTrue($executionState->hasCompletedStep('update_database'));
        $this->assertTrue($executionState->hasCompletedStep('send_email'));
        $this->assertEquals(1, Cache::get('getData'));
        $this->assertEquals(2, Cache::get('updateDatabase'));
        $this->assertEquals(1, Cache::get('sendEmail'));
    }

    public function test_job_can_define_custom_execution_context_id()
    {
        Mail::fake();
        Event::fake();

        TestResumableJobWithCustomExecutionContext::dispatch('return-1234', 1234);
        $this->runQueueWorkerCommand(['--once' => true]);
        $executionState = Cache::get('execution:return-1234');

        $this->assertInstanceof(ExecutionState::class, $executionState);
        $this->assertTrue($executionState->hasCompletedStep('get_data'));
        $this->assertTrue($executionState->hasCompletedStep('update_database'));
        $this->assertTrue($executionState->hasCompletedStep('send_email'));
    }

    public function test_job_can_define_execution_context_options()
    {
        $this->travelTo('2025-06-29T00:00:01.000Z');
        Mail::fake();
        Event::fake();

        TestResumableJobWithExecutionContextOptions::dispatch('return-1234', 1234);
        $this->runQueueWorkerCommand(['--once' => true]);

        $this->assertInstanceof(ExecutionState::class, Cache::get('execution:return-1234'));

        $this->travelTo('2025-06-29T00:01:02.000Z');

        $this->assertNull(Cache::get('execution:return-1234'));
    }

    public function test_jobs_can_share_an_execution_context()
    {
        Mail::fake();
        Event::fake();

        Cache::put('execution:return-1234', new ExecutionState('return-1234', [
            'get_data' => [
                'completed_at' => 1,
                'result' => [
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
                ],
            ],
        ]));

        TestResumableJobWithCustomExecutionContext::dispatch('return-1234', 1234);
        $this->runQueueWorkerCommand(['--once' => true]);

        $this->assertEquals(0, Cache::get('getData'));
        $this->assertEquals(1, Cache::get('updateDatabase'));
        $this->assertEquals(1, Cache::get('sendEmail'));

        TestResumableJobWithCustomExecutionContext::dispatch('return-1234', 1234);
        $this->runQueueWorkerCommand(['--once' => true]);

        $executionState = Cache::get('execution:return-1234');

        $this->assertInstanceof(ExecutionState::class, $executionState);
        $this->assertTrue($executionState->hasCompletedStep('get_data'));
        $this->assertTrue($executionState->hasCompletedStep('update_database'));
        $this->assertTrue($executionState->hasCompletedStep('send_email'));
        $this->assertEquals(0, Cache::get('getData'));
        $this->assertEquals(1, Cache::get('updateDatabase'));
        $this->assertEquals(1, Cache::get('sendEmail'));
    }
}

class TestResumableJob implements Resumable, ShouldQueue
{
    use InteractsWithQueue;
    use ResumableTrait;
    use Dispatchable;

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
        Cache::increment('updateDatabase');
        if (Cache::get('throw_exception')) {
            Cache::forget('throw_exception');

            throw new \Exception('You asked me to throw this');
        }
    }

    private function sendEmail(): array
    {
        Cache::increment('sendEmail');
        $recipients = [];

        $users = $this->context->getState()->resultFor('get_data')['data'];
        foreach($users as $user) {
            Mail::to($user['email'])->send((new Mailable)->subject('test email: '.$this->userId));
            $recipients[] = $user['id'];
        }

        return $recipients;
    }
}

class TestResumableJobWithCustomExecutionContext extends TestResumableJob
{
    public function __construct(
        protected string $contextId,
        int $userId,
    ) {
        parent::__construct($userId);
    }

    public function executionContextId(): mixed
    {
        return $this->contextId;
    }
}

class TestResumableJobWithExecutionContextOptions extends TestResumableJobWithCustomExecutionContext
{
    public function executionContextOptions(): array
    {
        return ['ttl' => 60];
    }
}
