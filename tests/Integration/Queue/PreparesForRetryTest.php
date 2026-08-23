<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Illuminate\Contracts\Queue\PreparesForRetry;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Failed\FailedJobProviderInterface;
use Mockery;
use Orchestra\Testbench\TestCase;

class PreparesForRetryTest extends TestCase
{
    public function test_job_is_not_retried_when_prepare_returns_false()
    {
        $this->app->instance('queue.failer', $failer = Mockery::mock(FailedJobProviderInterface::class));
        $failer->expects('find')->with('1')->andReturn($this->failedJob(new PreparesForRetryFalseJob));
        $failer->expects('forget')->never();

        $this->app->instance('queue', $queues = Mockery::mock(QueueFactory::class));
        $queues->shouldReceive('connection')->with('sync')->andReturn($queue = Mockery::mock(Queue::class));
        $queue->expects('pushRaw')->never();

        $this->artisan('queue:retry', ['id' => ['1']])->assertSuccessful();
    }

    public function test_prepared_job_is_retried_with_its_changes()
    {
        $this->app->instance('queue.failer', $failer = Mockery::mock(FailedJobProviderInterface::class));
        $failer->expects('find')->with('1')->andReturn($this->failedJob(new PreparesForRetryCountingJob(2)));
        $failer->expects('forget')->with('1');

        $this->app->instance('queue', $queues = Mockery::mock(QueueFactory::class));
        $queues->shouldReceive('connection')->with('sync')->andReturn($queue = Mockery::mock(Queue::class));
        $queue->expects('pushRaw')->withArgs(function ($payload, $queue) {
            $job = unserialize(json_decode($payload, true)['data']['command']);

            return $job instanceof PreparesForRetryCountingJob && $job->attempt === 3 && $queue === 'default';
        });

        $this->artisan('queue:retry', ['id' => ['1']])->assertSuccessful();
    }

    protected function failedJob($job)
    {
        return (object) [
            'id' => '1',
            'connection' => 'sync',
            'queue' => 'default',
            'payload' => json_encode([
                'uuid' => 'uuid',
                'displayName' => $job::class,
                'job' => 'Illuminate\Queue\CallQueuedHandler@call',
                'attempts' => 3,
                'data' => ['commandName' => $job::class, 'command' => serialize($job)],
            ]),
        ];
    }
}

class PreparesForRetryFalseJob implements PreparesForRetry, ShouldQueue
{
    use Queueable;

    public function prepareForRetry(): bool
    {
        return false;
    }

    public function handle(): void
    {
    }
}

class PreparesForRetryCountingJob implements PreparesForRetry, ShouldQueue
{
    use Queueable;

    public function __construct(public int $attempt)
    {
    }

    public function prepareForRetry(): void
    {
        $this->attempt++;
    }

    public function handle(): void
    {
    }
}
