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
        $this->travelTo('2025-06-29T00:00:01.000Z');
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
        $this->travelTo('2025-06-29T00:00:01.000Z');
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

    public function test_resumes_with_job_failure()
    {
        $uuid = Str::freezeUuids();
        $this->travelTo('2025-06-29T00:00:01.000Z');
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
