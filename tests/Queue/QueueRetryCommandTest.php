<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Foundation\Application;
use Illuminate\Queue\Console\RetryCommand;
use Illuminate\Queue\Events\JobRetryRequested;
use Illuminate\Queue\Failed\FailedJobProviderInterface;
use Illuminate\Queue\SqsQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class QueueRetryCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testRetriesSingleJobByPushingItsRawPayload()
    {
        $failer = m::mock(FailedJobProviderInterface::class);
        $queue = m::mock(QueueContract::class);

        $job = $this->failedJob(id: '5', connection: 'database', queue: 'default');

        $failer->shouldReceive('find')->once()->with('5')->andReturn($job);
        $queue->shouldReceive('pushRaw')->once()->with($this->retriedPayload(id: '5'), 'default', []);
        $failer->shouldReceive('forget')->once()->with('5');

        $this->runRetryCommand(['id' => ['5']], $failer, ['database' => $queue]);
    }

    public function testRetriesSingleJobByPushingItsRawPayloadWithOptions()
    {
        $failer = m::mock(FailedJobProviderInterface::class);
        $queue = m::mock(SqsQueue::class);

        $job = $this->failedJob(id: '5', connection: 'database', queue: 'default');

        $failer->shouldReceive('find')->once()->with('5')->andReturn($job);
        $queue->shouldReceive('getQueueableOptions')
            ->once()
            ->with(m::type(QueueRetryCommandTestJob::class), 'default', $job->payload)
            ->andReturn(['MySpecialOption' => 'option-1']);
        $queue->shouldReceive('pushRaw')->once()->with($this->retriedPayload(id: '5'), 'default', ['MySpecialOption' => 'option-1']);
        $failer->shouldReceive('forget')->once()->with('5');

        $this->runRetryCommand(['id' => ['5']], $failer, ['database' => $queue]);
    }

    public function testDisplaysErrorWhenJobIsNotFound()
    {
        $failer = m::mock(FailedJobProviderInterface::class);
        $failer->shouldReceive('find')->once()->with('123')->andReturn(null);

        $output = $this->runRetryCommand(['id' => ['123']], $failer, []);

        $this->assertStringContainsString('Unable to find failed job with ID [123].', $output);
    }

    public function testRetriesAllFailedJobsUsingTheProvidersIds()
    {
        $failer = m::mock(FailedJobProviderInterface::class);
        $queue = m::mock(QueueContract::class);

        $failer->shouldReceive('ids')->once()->withNoArgs()->andReturn(['1', '2']);

        $failer->shouldReceive('find')->once()->with('1')->andReturn($this->failedJob(id: '1', connection: 'database', queue: 'default'));
        $queue->shouldReceive('pushRaw')->once()->with($this->retriedPayload(id: '1'), 'default', []);
        $failer->shouldReceive('forget')->once()->with('1');

        $failer->shouldReceive('find')->once()->with('2')->andReturn($this->failedJob(id: '2', connection: 'database', queue: 'emails'));
        $queue->shouldReceive('pushRaw')->once()->with($this->retriedPayload(id: '2'), 'emails', []);
        $failer->shouldReceive('forget')->once()->with('2');

        $output = $this->runRetryCommand(['id' => ['all']], $failer, ['database' => $queue]);

        $this->assertStringContainsString('Pushing failed queue jobs back onto the queue.', $output);
        $this->assertStringContainsString('DONE', $output);
    }

    public function testRetriesJobsOnTheSpecifiedQueue()
    {
        $failer = m::mock(FailedJobProviderInterface::class);
        $queue = m::mock(QueueContract::class);

        $failer->shouldReceive('ids')->once()->with('emails')->andReturn(['2']);
        $failer->shouldReceive('find')->once()->with('2')->andReturn($this->failedJob(id: '2', connection: 'database', queue: 'emails'));
        $queue->shouldReceive('pushRaw')->once()->with($this->retriedPayload(id: '2'), 'emails', []);
        $failer->shouldReceive('forget')->once()->with('2');

        $this->runRetryCommand(['--queue' => 'emails'], $failer, ['database' => $queue]);
    }

    public function testDisplaysErrorWhenTheSpecifiedQueueHasNoFailedJobs()
    {
        $failer = m::mock(FailedJobProviderInterface::class);
        $failer->shouldReceive('ids')->once()->with('emails')->andReturn([]);

        $output = $this->runRetryCommand(['--queue' => 'emails'], $failer, []);

        $this->assertStringContainsString('Unable to find failed jobs for queue [emails].', $output);
        $this->assertStringContainsString('No retryable jobs found.', $output);
    }

    public function testRetriesJobsWithinTheGivenIdRange()
    {
        $failer = m::mock(FailedJobProviderInterface::class);
        $queue = m::mock(QueueContract::class);

        $failer->shouldReceive('find')->once()->with(1)->andReturn($this->failedJob(id: '1', connection: 'database', queue: 'default'));
        $queue->shouldReceive('pushRaw')->once()->with($this->retriedPayload(id: '1'), 'default', []);
        $failer->shouldReceive('forget')->once()->with(1);

        $failer->shouldReceive('find')->once()->with(2)->andReturn($this->failedJob(id: '2', connection: 'database', queue: 'default'));
        $queue->shouldReceive('pushRaw')->once()->with($this->retriedPayload(id: '2'), 'default', []);
        $failer->shouldReceive('forget')->once()->with(2);

        $failer->shouldReceive('find')->once()->with(3)->andReturn($this->failedJob(id: '3', connection: 'database', queue: 'default'));
        $queue->shouldReceive('pushRaw')->once()->with($this->retriedPayload(id: '3'), 'default', []);
        $failer->shouldReceive('forget')->once()->with(3);

        $this->runRetryCommand(['--range' => ['1-3']], $failer, ['database' => $queue]);
    }

    public function testDisplaysInfoWhenThereAreNoJobsToRetry()
    {
        $failer = m::mock(FailedJobProviderInterface::class);

        $output = $this->runRetryCommand(['id' => []], $failer, []);

        $this->assertStringContainsString('No retryable jobs found.', $output);
    }

    public function testItResetsAttemptsCountWhenRetryingAJob()
    {
        $failer = m::mock(FailedJobProviderInterface::class);
        $queue = m::mock(QueueContract::class);

        $job = $this->failedJob(id: '1', connection: 'database', queue: 'default', payload: ['attempts' => 5]);

        $failer->shouldReceive('find')->once()->with('1')->andReturn($job);
        $queue->shouldReceive('pushRaw')->once()->with(m::on(function ($payload) {
            return json_decode($payload, true)['attempts'] === 0;
        }), 'default', []);
        $failer->shouldReceive('forget')->once()->with('1');

        $this->runRetryCommand(['id' => ['1']], $failer, ['database' => $queue]);
    }

    public function testRefreshesTheRetryUntilTimestampWhenTheJobDefinesRetryUntil()
    {
        $failer = m::mock(FailedJobProviderInterface::class);
        $queue = m::mock(QueueContract::class);

        $job = $this->failedJob(
            id: '1',
            connection: 'database',
            queue: 'default',
            payload: ['retryUntil' => 0],
            job: new QueueRetryCommandTestJobWithRetryUntil(retryUntil: 1234567890)
        );

        $failer->shouldReceive('find')->once()->with('1')->andReturn($job);
        $queue->shouldReceive('pushRaw')->once()->with(m::on(function ($payload) {
            return json_decode($payload, true)['retryUntil'] === 1234567890;
        }), 'default', []);
        $failer->shouldReceive('forget')->once()->with('1');

        $this->runRetryCommand(['id' => ['1']], $failer, ['database' => $queue]);
    }

    public function testPassesQueueableOptionsToTheQueueWhenRetryingASingleJob()
    {
        $failer = m::mock(FailedJobProviderInterface::class);
        $queue = m::mock(SqsQueue::class);

        $job = $this->failedJob(id: '1', connection: 'sqs', queue: 'default');

        $failer->shouldReceive('find')->once()->with('1')->andReturn($job);
        $queue->shouldReceive('getQueueableOptions')
            ->once()
            ->with(m::type(QueueRetryCommandTestJob::class), 'default', $job->payload)
            ->andReturn(['MySpecialOption' => 'option-1']);
        $queue->shouldReceive('pushRaw')->once()->with(m::type('string'), 'default', ['MySpecialOption' => 'option-1']);
        $failer->shouldReceive('forget')->once()->with('1');

        $this->runRetryCommand(['id' => ['1']], $failer, ['sqs' => $queue]);
    }

    public function testDispatchesRetryRequestedEventWhenRetryingASingleJob()
    {
        $failer = m::mock(FailedJobProviderInterface::class);
        $queue = m::mock(QueueContract::class);
        $events = m::mock(Dispatcher::class);

        $job = $this->failedJob(id: '1', connection: 'database', queue: 'default');

        $failer->shouldReceive('find')->once()->with('1')->andReturn($job);
        $events->shouldReceive('dispatch')->once()->with(m::type(JobRetryRequested::class));
        $queue->shouldReceive('pushRaw')->once();
        $failer->shouldReceive('forget')->once()->with('1');

        $this->runRetryCommand(['id' => ['1']], $failer, ['database' => $queue], $events);
    }

    public function testRetriesCollectionOfJobs()
    {
        $failer = m::mock(FailedJobProviderInterface::class);
        $queue = m::mock(QueueContract::class);

        $jobs = new Collection([
            'job-1' => $this->failedJob(id: 'job-1', connection: 'database', queue: 'default'),
            'job-2' => $this->failedJob(id: 'job-2', connection: 'database', queue: 'default'),
        ]);

        $failer->shouldReceive('find')->once()->with('batch')->andReturn($jobs);

        $queue->shouldReceive('pushRaw')->once()->with($this->retriedPayload(id: 'job-1'), 'default', []);
        $failer->shouldReceive('forget')->once()->with('job-1');

        $queue->shouldReceive('pushRaw')->once()->with($this->retriedPayload(id: 'job-2'), 'default', []);
        $failer->shouldReceive('forget')->once()->with('job-2');

        $this->runRetryCommand(['id' => ['batch']], $failer, ['database' => $queue]);
    }

    public function testRetriesALazyCollectionOfJobsAndDoesNotResolveThemAllEagerly()
    {
        $failer = m::mock(FailedJobProviderInterface::class);
        $queue = m::mock(QueueContract::class);

        $unresolvedCounts = [];

        $pendingJobs = [
            'job-1' => $this->failedJob(id: 'job-1', connection: 'database', queue: 'default'),
            'job-2' => $this->failedJob(id: 'job-2', connection: 'database', queue: 'default'),
            'job-3' => $this->failedJob(id: 'job-3', connection: 'database', queue: 'default'),
        ];

        $iterator = value(function () use (&$pendingJobs) {
            while ($job = array_shift($pendingJobs)) {
                yield $job->id => $job;
            }
        });

        $jobs = new LazyCollection(fn () => yield from $iterator);

        $failer->shouldReceive('find')->once()->with('batch')->andReturn($jobs);

        $queue->shouldReceive('pushRaw')->once()->with($this->retriedPayload(id: 'job-1'), 'default', []);
        $failer->shouldReceive('forget')->once()->with('job-1')->andReturnUsing(function () use (&$unresolvedCounts, &$pendingJobs) {
            $unresolvedCounts[] = count($pendingJobs);
        });

        $queue->shouldReceive('pushRaw')->once()->with($this->retriedPayload(id: 'job-2'), 'default', []);
        $failer->shouldReceive('forget')->once()->with('job-2')->andReturnUsing(function () use (&$unresolvedCounts, &$pendingJobs) {
            $unresolvedCounts[] = count($pendingJobs);
        });

        $queue->shouldReceive('pushRaw')->once()->with($this->retriedPayload(id: 'job-3'), 'default', []);
        $failer->shouldReceive('forget')->once()->with('job-3')->andReturnUsing(function () use (&$unresolvedCounts, &$pendingJobs) {
            $unresolvedCounts[] = count($pendingJobs);
        });

        $this->runRetryCommand(['id' => ['batch']], $failer, ['database' => $queue]);

        $this->assertCount(3, $unresolvedCounts);
        $this->assertSame(2, $unresolvedCounts[0]);
        $this->assertSame(1, $unresolvedCounts[1]);
        $this->assertSame(0, $unresolvedCounts[2]);
    }

    public function testItResetsAttemptsCountWhenRetryingACollectionOfJobs()
    {
        $failer = m::mock(FailedJobProviderInterface::class);
        $queue = m::mock(QueueContract::class);

        $jobs = new Collection([
            'job-1' => $this->failedJob(id: 'job-1', connection: 'database', queue: 'default', payload: ['attempts' => 5]),
        ]);

        $failer->shouldReceive('find')->once()->with('batch')->andReturn($jobs);
        $queue->shouldReceive('pushRaw')->once()->with(m::on(function ($payload) {
            return json_decode($payload, true)['attempts'] === 0;
        }), 'default', []);
        $failer->shouldReceive('forget')->once()->with('job-1');

        $this->runRetryCommand(['id' => ['batch']], $failer, ['database' => $queue]);
    }

    public function testRefreshesTheRetryUntilTimestampWhenRetryingACollectionOfJobs()
    {
        $failer = m::mock(FailedJobProviderInterface::class);
        $queue = m::mock(QueueContract::class);

        $jobs = new Collection([
            'job-1' => $this->failedJob(
                id: 'job-1',
                connection: 'database',
                queue: 'default',
                payload: ['retryUntil' => 0],
                job: new QueueRetryCommandTestJobWithRetryUntil(retryUntil: 1234567890)
            ),
        ]);

        $failer->shouldReceive('find')->once()->with('batch')->andReturn($jobs);
        $queue->shouldReceive('pushRaw')->once()->with(m::on(function ($payload) {
            return json_decode($payload, true)['retryUntil'] === 1234567890;
        }), 'default', []);
        $failer->shouldReceive('forget')->once()->with('job-1');

        $this->runRetryCommand(['id' => ['batch']], $failer, ['database' => $queue]);
    }

    public function testDisplaysErrorWhenTheGivenIdResolvesToAnEmptyCollection()
    {
        $failer = m::mock(FailedJobProviderInterface::class);
        $failer->shouldReceive('find')->once()->with('batch')->andReturn(new Collection);

        $output = $this->runRetryCommand(['id' => ['batch']], $failer, []);

        $this->assertStringContainsString('Pushing failed queue jobs back onto the queue.', $output);
        $this->assertStringContainsString('Unable to find any failed jobs with ID [batch].', $output);
        $this->assertStringNotContainsString('No retryable jobs found.', $output);
    }

    public function testDisplaysErrorWhenTheGivenIdResolvesToAnEmptyLazyCollection()
    {
        $failer = m::mock(FailedJobProviderInterface::class);

        $resolved = false;

        $jobs = new LazyCollection(function () use (&$resolved) {
            $resolved = true;

            yield from [];
        });

        $failer->shouldReceive('find')->once()->with('batch')->andReturn($jobs);

        $output = $this->runRetryCommand(['id' => ['batch']], $failer, []);

        $this->assertTrue($resolved);
        $this->assertStringContainsString('Unable to find any failed jobs with ID [batch].', $output);
        $this->assertStringNotContainsString('No retryable jobs found.', $output);
    }

    public function testContinuesRetryingRemainingJobsAfterAJobIsNotFound()
    {
        $failer = m::mock(FailedJobProviderInterface::class);
        $queue = m::mock(QueueContract::class);

        $failer->shouldReceive('find')->once()->with('1')->andReturn(null);

        $failer->shouldReceive('find')->once()->with('2')->andReturn($this->failedJob(id: '2', connection: 'database', queue: 'default'));
        $queue->shouldReceive('pushRaw')->once()->with($this->retriedPayload(id: '2'), 'default', []);
        $failer->shouldReceive('forget')->once()->with('2');

        $output = $this->runRetryCommand(['id' => ['1', '2']], $failer, ['database' => $queue]);

        $this->assertStringContainsString('Unable to find failed job with ID [1].', $output);
        $this->assertStringContainsString('DONE', $output);
    }

    public function testContinuesRetryingRemainingJobsAfterAnIdResolvesToAnEmptyCollection()
    {
        $failer = m::mock(FailedJobProviderInterface::class);
        $queue = m::mock(QueueContract::class);

        $failer->shouldReceive('find')->once()->with('batch')->andReturn(new Collection);

        $failer->shouldReceive('find')->once()->with('2')->andReturn($this->failedJob(id: '2', connection: 'database', queue: 'default'));
        $queue->shouldReceive('pushRaw')->once()->with($this->retriedPayload(id: '2'), 'default', []);
        $failer->shouldReceive('forget')->once()->with('2');

        $output = $this->runRetryCommand(['id' => ['batch', '2']], $failer, ['database' => $queue]);

        $this->assertStringContainsString('Unable to find any failed jobs with ID [batch].', $output);
        $this->assertStringContainsString('DONE', $output);
    }

    public function testRetriesAMixtureOfSingleJobsAndCollectionsOfJobs()
    {
        $failer = m::mock(FailedJobProviderInterface::class);
        $queue = m::mock(QueueContract::class);

        $failer->shouldReceive('find')->once()->with('batch')->andReturn(new Collection([
            'job-1' => $this->failedJob(id: 'job-1', connection: 'database', queue: 'default'),
            'job-2' => $this->failedJob(id: 'job-2', connection: 'database', queue: 'default'),
        ]));

        $failer->shouldReceive('find')->once()->with('9')->andReturn($this->failedJob(id: '9', connection: 'database', queue: 'default'));

        $queue->shouldReceive('pushRaw')->once()->with($this->retriedPayload(id: 'job-1'), 'default', []);
        $failer->shouldReceive('forget')->once()->with('job-1');

        $queue->shouldReceive('pushRaw')->once()->with($this->retriedPayload(id: 'job-2'), 'default', []);
        $failer->shouldReceive('forget')->once()->with('job-2');

        $queue->shouldReceive('pushRaw')->once()->with($this->retriedPayload(id: '9'), 'default', []);
        $failer->shouldReceive('forget')->once()->with('9');

        $this->runRetryCommand(['id' => ['batch', '9']], $failer, ['database' => $queue]);
    }

    public function testForgetsJobsUsingTheCollectionKeyRatherThanTheJobId()
    {
        $failer = m::mock(FailedJobProviderInterface::class);
        $queue = m::mock(QueueContract::class);

        $key = 'https://cloud.test/failed-jobs/batch-1:job-1';

        $failer->shouldReceive('find')->once()->with('batch')->andReturn(new Collection([
            $key => $this->failedJob(id: 'job-1', connection: 'database', queue: 'default'),
        ]));

        $queue->shouldReceive('pushRaw')->once()->with($this->retriedPayload(id: 'job-1'), 'default', []);
        $failer->shouldReceive('forget')->once()->with($key);

        $output = $this->runRetryCommand(['id' => ['batch']], $failer, ['database' => $queue]);

        $this->assertMatchesRegularExpression('/^  '.preg_quote($key, '/').' \.+/m', $output);
    }

    public function testForgetsJobsUsingTheCollectionKeyWhenTheCollectionIsNotKeyed()
    {
        $failer = m::mock(FailedJobProviderInterface::class);
        $queue = m::mock(QueueContract::class);

        $failer->shouldReceive('find')->once()->with('batch')->andReturn(new Collection([
            $this->failedJob(id: 'job-1', connection: 'database', queue: 'default'),
            $this->failedJob(id: 'job-2', connection: 'database', queue: 'default'),
        ]));

        $queue->shouldReceive('pushRaw')->once()->with($this->retriedPayload(id: 'job-1'), 'default', []);
        $failer->shouldReceive('forget')->once()->with(0);

        $queue->shouldReceive('pushRaw')->once()->with($this->retriedPayload(id: 'job-2'), 'default', []);
        $failer->shouldReceive('forget')->once()->with(1);

        $this->runRetryCommand(['id' => ['batch']], $failer, ['database' => $queue]);
    }

    public function testDispatchesRetryRequestedEventForEveryJobInACollection()
    {
        $failer = m::mock(FailedJobProviderInterface::class);
        $queue = m::mock(QueueContract::class);
        $events = m::mock(Dispatcher::class);

        $jobs = new Collection([
            'job-1' => $this->failedJob(id: 'job-1', connection: 'database', queue: 'default'),
            'job-2' => $this->failedJob(id: 'job-2', connection: 'database', queue: 'default'),
        ]);

        $failer->shouldReceive('find')->once()->with('batch')->andReturn($jobs);

        $dispatched = [];

        $events->shouldReceive('dispatch')->twice()->with(m::type(JobRetryRequested::class))->andReturnUsing(function ($event) use (&$dispatched) {
            $dispatched[] = $event->job->id;
        });

        $queue->shouldReceive('pushRaw')->twice();
        $failer->shouldReceive('forget')->once()->with('job-1');
        $failer->shouldReceive('forget')->once()->with('job-2');

        $this->runRetryCommand(['id' => ['batch']], $failer, ['database' => $queue], $events);

        $this->assertSame(['job-1', 'job-2'], $dispatched);
    }

    public function testStopsRetryingWhenAJobInACollectionFailsToBePushed()
    {
        $failer = m::mock(FailedJobProviderInterface::class);
        $queue = m::mock(QueueContract::class);

        $pendingJobs = [
            'job-1' => $this->failedJob(id: 'job-1', connection: 'database', queue: 'default'),
            'job-2' => $this->failedJob(id: 'job-2', connection: 'database', queue: 'default'),
            'job-3' => $this->failedJob(id: 'job-3', connection: 'database', queue: 'default'),
        ];

        $yielded = 0;
        $iterator = value(function () use (&$pendingJobs, &$yielded) {
            while ($job = array_shift($pendingJobs)) {
                $yielded++;
                yield $job->id => $job;
            }
        });

        $jobs = new LazyCollection(fn () => yield from $iterator);

        $failer->shouldReceive('find')->once()->with('batch')->andReturn($jobs);

        $queue->shouldReceive('pushRaw')->once()->with($this->retriedPayload(id: 'job-1'), 'default', []);
        $failer->shouldReceive('forget')->once()->with('job-1');

        $queue->shouldReceive('pushRaw')->once()->with($this->retriedPayload(id: 'job-2'), 'default', [])->andThrow(new RuntimeException('Unable to push job.'));
        $failer->shouldNotReceive('forget')->with('job-2');

        $queue->shouldNotReceive('pushRaw')->with($this->retriedPayload(id: 'job-3'), 'default', []);
        $failer->shouldNotReceive('forget')->with('job-3');

        try {
            $this->runRetryCommand(['id' => ['batch']], $failer, ['database' => $queue]);

            $this->fail('The exception was not thrown.');
        } catch (RuntimeException $e) {
            $this->assertSame('Unable to push job.', $e->getMessage());
        }

        $this->assertSame(['job-3'], array_keys($pendingJobs));
    }

    public function testDispatchesTheEventBeforePushingAndForgetsTheJobAfterwards()
    {
        $failer = m::mock(FailedJobProviderInterface::class);
        $queue = m::mock(QueueContract::class);
        $events = m::mock(Dispatcher::class);

        $jobs = new Collection([
            'job-1' => $this->failedJob(id: 'job-1', connection: 'database', queue: 'default'),
        ]);

        $failer->shouldReceive('find')->once()->with('batch')->andReturn($jobs);

        $sequence = [];

        $events->shouldReceive('dispatch')->once()->with(m::type(JobRetryRequested::class))->andReturnUsing(function () use (&$sequence) {
            $sequence[] = 'dispatch';
        });

        $queue->shouldReceive('pushRaw')->once()->with($this->retriedPayload(id: 'job-1'), 'default', [])->andReturnUsing(function () use (&$sequence) {
            $sequence[] = 'push';
        });

        $failer->shouldReceive('forget')->once()->with('job-1')->andReturnUsing(function () use (&$sequence) {
            $sequence[] = 'forget';
        });

        $this->runRetryCommand(['id' => ['batch']], $failer, ['database' => $queue], $events);

        $this->assertSame(['dispatch', 'push', 'forget'], $sequence);
    }

    public function testOutputsAnEntryForEveryJobInACollection()
    {
        $failer = m::mock(FailedJobProviderInterface::class);
        $queue = m::mock(QueueContract::class);

        $jobs = new Collection([
            'job-1' => $this->failedJob(id: 'job-1', connection: 'database', queue: 'default'),
            'job-2' => $this->failedJob(id: 'job-2', connection: 'database', queue: 'default'),
        ]);

        $failer->shouldReceive('find')->once()->with('batch')->andReturn($jobs);

        $queue->shouldReceive('pushRaw')->twice();
        $failer->shouldReceive('forget')->once()->with('job-1');
        $failer->shouldReceive('forget')->once()->with('job-2');

        $output = $this->runRetryCommand(['id' => ['batch']], $failer, ['database' => $queue]);

        $this->assertSame(1, substr_count($output, 'Pushing failed queue jobs back onto the queue.'));
        $this->assertSame(1, substr_count($output, 'job-1'));
        $this->assertSame(1, substr_count($output, 'job-2'));
        $this->assertSame(2, substr_count($output, 'DONE'));
    }

    public function testRetriesCollectionsOfJobsWhenRetryingAllFailedJobs()
    {
        $failer = m::mock(FailedJobProviderInterface::class);
        $queue = m::mock(QueueContract::class);

        $failer->shouldReceive('ids')->once()->withNoArgs()->andReturn(['batch-1', 'batch-2']);

        $failer->shouldReceive('find')->once()->with('batch-1')->andReturn(new Collection([
            'job-1' => $this->failedJob(id: 'job-1', connection: 'database', queue: 'default'),
        ]));

        $failer->shouldReceive('find')->once()->with('batch-2')->andReturn(new Collection([
            'job-2' => $this->failedJob(id: 'job-2', connection: 'database', queue: 'emails'),
        ]));

        $queue->shouldReceive('pushRaw')->once()->with($this->retriedPayload(id: 'job-1'), 'default', []);
        $failer->shouldReceive('forget')->once()->with('job-1');

        $queue->shouldReceive('pushRaw')->once()->with($this->retriedPayload(id: 'job-2'), 'emails', []);
        $failer->shouldReceive('forget')->once()->with('job-2');

        $this->runRetryCommand(['id' => ['all']], $failer, ['database' => $queue]);
    }

    public function testRetriesTheSameJobTwiceWhenItAppearsInTwoCollections()
    {
        $failer = m::mock(FailedJobProviderInterface::class);
        $queue = m::mock(QueueContract::class);

        $failer->shouldReceive('find')->once()->with('batch-1')->andReturn(new Collection([
            'job-1' => $this->failedJob(id: 'job-1', connection: 'database', queue: 'default'),
        ]));

        $failer->shouldReceive('find')->once()->with('batch-2')->andReturn(new Collection([
            'job-1' => $this->failedJob(id: 'job-1', connection: 'database', queue: 'default'),
        ]));

        $queue->shouldReceive('pushRaw')->twice()->with($this->retriedPayload(id: 'job-1'), 'default', []);
        $failer->shouldReceive('forget')->twice()->with('job-1');

        $this->runRetryCommand(['id' => ['batch-1', 'batch-2']], $failer, ['database' => $queue]);
    }

    private function failedJob(
        string $id,
        string $connection,
        string $queue,
        array $payload = [],
        $job = new QueueRetryCommandTestJob,
    ): stdClass {
        return (object) [
            'id' => $id,
            'connection' => $connection,
            'queue' => $queue,
            'payload' => json_encode([
                'uuid' => $id,
                'displayName' => get_class($job),
                'data' => [
                    'commandName' => get_class($job),
                    'command' => serialize($job),
                ],
                ...$payload,
            ]),
        ];
    }

    private function retriedPayload(string $id, $job = new QueueRetryCommandTestJob): string
    {
        return json_encode([
            'uuid' => $id,
            'displayName' => QueueRetryCommandTestJob::class,
            'data' => [
                'commandName' => get_class($job),
                'command' => serialize($job),
            ],
        ]);
    }

    private function runRetryCommand(array $input, FailedJobProviderInterface $failer, array $connections, $events = null): string
    {
        $container = new Application;

        $container->instance('queue.failer', $failer);

        $manager = m::mock(\stdClass::class);

        foreach ($connections as $name => $queue) {
            $manager->shouldReceive('connection')->with($name)->andReturn($queue);
        }

        $container->instance('queue', $manager);

        if (is_null($events)) {
            $events = m::mock(Dispatcher::class);
            $events->shouldReceive('dispatch');
        }

        $container->instance('events', $events);

        $command = new RetryCommand;
        $command->setLaravel($container);

        $output = new BufferedOutput;
        $command->run(new ArrayInput($input), $output);

        return $output->fetch();
    }
}

class QueueRetryCommandTestJob
{
    //
}

class QueueRetryCommandTestJobWithRetryUntil
{
    public function __construct(private int $retryUntil = 0)
    {
        //
    }

    public function retryUntil()
    {
        return $this->retryUntil;
    }
}
