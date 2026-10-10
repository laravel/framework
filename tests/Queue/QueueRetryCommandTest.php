<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Foundation\Application;
use Illuminate\Queue\Console\RetryCommand;
use Illuminate\Queue\Events\JobRetryRequested;
use Illuminate\Queue\Failed\FailedJobProviderInterface;
use Illuminate\Queue\Failed\NullFailedJobProvider;
use Illuminate\Queue\QueueManager;
use Illuminate\Queue\SqsQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use JMac\Testing\Matching\Argument;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class QueueRetryCommandTest extends TestCase
{
    use VerifiesDoubles;

    protected function tearDown(): void
    {
    }

    public function testRetriesSingleJobByPushingItsRawPayload()
    {
        $failer = Double::for(FailedJobProviderInterface::class);
        $queue = Double::for(QueueContract::class);

        $job = $this->failedJob(id: '5', connection: 'database', queue: 'default');

        $failer->expects('find')->with('5')->returns($job);
        $queue->expects('pushRaw')->with($this->retriedPayload(id: '5'), 'default', []);
        $failer->expects('forget')->with(5);

        $this->runRetryCommand(['id' => ['5']], $failer, ['database' => $queue]);
    }

    public function testRetriesSingleJobByPushingItsRawPayloadWithOptions()
    {
        $failer = Double::for(FailedJobProviderInterface::class);
        $queue = Double::for(SqsQueue::class);

        $job = $this->failedJob(id: '5', connection: 'database', queue: 'default');

        $failer->expects('find')->with('5')->returns($job);
        $queue->expects('getQueueableOptions')->with(Argument::type(QueueRetryCommandTestJob::class), 'default', $job->payload)->returns(['MySpecialOption' => 'option-1']);
        $queue->expects('pushRaw')->with($this->retriedPayload(id: '5'), 'default', ['MySpecialOption' => 'option-1']);
        $failer->expects('forget')->with(5);

        $this->runRetryCommand(['id' => ['5']], $failer, ['database' => $queue]);
    }

    public function testDisplaysErrorWhenJobIsNotFound()
    {
        $failer = Double::for(FailedJobProviderInterface::class);
        $failer->expects('find')->with('123')->returns(null);

        $output = $this->runRetryCommand(['id' => ['123']], $failer, []);

        $this->assertStringContainsString('Unable to find failed job with ID [123].', $output);
    }

    public function testRetriesAllFailedJobsUsingTheProvidersIds()
    {
        $failer = Double::for(FailedJobProviderInterface::class);
        $queue = Double::for(QueueContract::class);

        $failer->expects('ids')->with(Argument::none())->returns(['1', '2']);

        $failer->expects('find')->with('1')->returns($this->failedJob(id: '1', connection: 'database', queue: 'default'));
        $queue->expects('pushRaw')->with($this->retriedPayload(id: '1'), 'default', []);
        $failer->expects('forget')->with(1);

        $failer->expects('find')->with('2')->returns($this->failedJob(id: '2', connection: 'database', queue: 'emails'));
        $queue->expects('pushRaw')->with($this->retriedPayload(id: '2'), 'emails', []);
        $failer->expects('forget')->with(2);

        $output = $this->runRetryCommand(['id' => ['all']], $failer, ['database' => $queue]);

        $this->assertStringContainsString('Pushing failed queue jobs back onto the queue.', $output);
        $this->assertStringContainsString('DONE', $output);
    }

    public function testRetriesJobsOnTheSpecifiedQueue()
    {
        $failer = Double::for(FailedJobProviderInterface::class);
        $queue = Double::for(QueueContract::class);

        $failer->expects('ids')->with('emails')->returns(['2']);
        $failer->expects('find')->with('2')->returns($this->failedJob(id: '2', connection: 'database', queue: 'emails'));
        $queue->expects('pushRaw')->with($this->retriedPayload(id: '2'), 'emails', []);
        $failer->expects('forget')->with(2);

        $this->runRetryCommand(['--queue' => 'emails'], $failer, ['database' => $queue]);
    }

    public function testDisplaysErrorWhenTheSpecifiedQueueHasNoFailedJobs()
    {
        $failer = Double::for(FailedJobProviderInterface::class);
        $failer->expects('ids')->with('emails')->returns([]);

        $output = $this->runRetryCommand(['--queue' => 'emails'], $failer, []);

        $this->assertStringContainsString('Unable to find failed jobs for queue [emails].', $output);
        $this->assertStringContainsString('No retryable jobs found.', $output);
    }

    public function testRetriesJobsWithinTheGivenIdRange()
    {
        $failer = Double::for(FailedJobProviderInterface::class);
        $queue = Double::for(QueueContract::class);

        $failer->expects('find')->with('1')->returns($this->failedJob(id: '1', connection: 'database', queue: 'default'));
        $queue->expects('pushRaw')->with($this->retriedPayload(id: '1'), 'default', []);
        $failer->expects('forget')->with(1);

        $failer->expects('find')->with('2')->returns($this->failedJob(id: '2', connection: 'database', queue: 'default'));
        $queue->expects('pushRaw')->with($this->retriedPayload(id: '2'), 'default', []);
        $failer->expects('forget')->with(2);

        $failer->expects('find')->with('3')->returns($this->failedJob(id: '3', connection: 'database', queue: 'default'));
        $queue->expects('pushRaw')->with($this->retriedPayload(id: '3'), 'default', []);
        $failer->expects('forget')->with(3);

        $this->runRetryCommand(['--range' => ['1-3']], $failer, ['database' => $queue]);
    }

    public function testDisplaysInfoWhenThereAreNoJobsToRetry()
    {
        $failer = new NullFailedJobProvider;

        $output = $this->runRetryCommand(['id' => []], $failer, []);

        $this->assertStringContainsString('No retryable jobs found.', $output);
    }

    public function testItResetsAttemptsCountWhenRetryingAJob()
    {
        $failer = Double::for(FailedJobProviderInterface::class);
        $queue = Double::for(QueueContract::class);

        $job = $this->failedJob(id: '1', connection: 'database', queue: 'default', payload: ['attempts' => 5]);

        $failer->expects('find')->with('1')->returns($job);
        $queue->expects('pushRaw')->with(Argument::satisfies(function ($payload) {
            return json_decode($payload, true)['attempts'] === 0;
        }), 'default', []);
        $failer->expects('forget')->with(1);

        $this->runRetryCommand(['id' => ['1']], $failer, ['database' => $queue]);
    }

    public function testRefreshesTheRetryUntilTimestampWhenTheJobDefinesRetryUntil()
    {
        $failer = Double::for(FailedJobProviderInterface::class);
        $queue = Double::for(QueueContract::class);

        $job = $this->failedJob(
            id: '1',
            connection: 'database',
            queue: 'default',
            payload: ['retryUntil' => 0],
            job: new QueueRetryCommandTestJobWithRetryUntil(retryUntil: 1234567890)
        );

        $failer->expects('find')->with('1')->returns($job);
        $queue->expects('pushRaw')->with(Argument::satisfies(function ($payload) {
            return json_decode($payload, true)['retryUntil'] === 1234567890;
        }), 'default', []);
        $failer->expects('forget')->with(1);

        $this->runRetryCommand(['id' => ['1']], $failer, ['database' => $queue]);
    }

    public function testPassesQueueableOptionsToTheQueueWhenRetryingASingleJob()
    {
        $failer = Double::for(FailedJobProviderInterface::class);
        $queue = Double::for(SqsQueue::class);

        $job = $this->failedJob(id: '1', connection: 'sqs', queue: 'default');

        $failer->expects('find')->with('1')->returns($job);
        $queue->expects('getQueueableOptions')->with(Argument::type(QueueRetryCommandTestJob::class), 'default', $job->payload)->returns(['MySpecialOption' => 'option-1']);
        $queue->expects('pushRaw')->with(Argument::type('string'), 'default', ['MySpecialOption' => 'option-1']);
        $failer->expects('forget')->with(1);

        $this->runRetryCommand(['id' => ['1']], $failer, ['sqs' => $queue]);
    }

    public function testDispatchesRetryRequestedEventWhenRetryingASingleJob()
    {
        $failer = Double::for(FailedJobProviderInterface::class);
        $queue = Double::for(QueueContract::class);
        $events = Double::for(Dispatcher::class);

        $job = $this->failedJob(id: '1', connection: 'database', queue: 'default');

        $failer->expects('find')->with('1')->returns($job);
        $events->expects('dispatch')->with(Argument::type(JobRetryRequested::class));
        $queue->expects('pushRaw');
        $failer->expects('forget')->with(1);

        $this->runRetryCommand(['id' => ['1']], $failer, ['database' => $queue], $events);
    }

    public function testRetriesCollectionOfJobs()
    {
        $failer = Double::for(FailedJobProviderInterface::class);
        $queue = Double::for(QueueContract::class);

        $jobs = new Collection([
            'job-1' => $this->failedJob(id: 'job-1', connection: 'database', queue: 'default'),
            'job-2' => $this->failedJob(id: 'job-2', connection: 'database', queue: 'default'),
        ]);

        $failer->expects('find')->with('batch')->returns($jobs);

        $queue->expects('pushRaw')->with($this->retriedPayload(id: 'job-1'), 'default', []);
        $failer->expects('forget')->with('job-1');

        $queue->expects('pushRaw')->with($this->retriedPayload(id: 'job-2'), 'default', []);
        $failer->expects('forget')->with('job-2');

        $this->runRetryCommand(['id' => ['batch']], $failer, ['database' => $queue]);
    }

    public function testRetriesALazyCollectionOfJobsAndDoesNotResolveThemAllEagerly()
    {
        $failer = Double::for(FailedJobProviderInterface::class);
        $queue = Double::for(QueueContract::class);

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

        $failer->expects('find')->with('batch')->returns($jobs);

        $queue->expects('pushRaw')->with($this->retriedPayload(id: 'job-1'), 'default', []);
        $failer->expects('forget')->with('job-1')->resolves(function () use (&$unresolvedCounts, &$pendingJobs) {
            $unresolvedCounts[] = count($pendingJobs);
        });

        $queue->expects('pushRaw')->with($this->retriedPayload(id: 'job-2'), 'default', []);
        $failer->expects('forget')->with('job-2')->resolves(function () use (&$unresolvedCounts, &$pendingJobs) {
            $unresolvedCounts[] = count($pendingJobs);
        });

        $queue->expects('pushRaw')->with($this->retriedPayload(id: 'job-3'), 'default', []);
        $failer->expects('forget')->with('job-3')->resolves(function () use (&$unresolvedCounts, &$pendingJobs) {
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
        $failer = Double::for(FailedJobProviderInterface::class);
        $queue = Double::for(QueueContract::class);

        $jobs = new Collection([
            'job-1' => $this->failedJob(id: 'job-1', connection: 'database', queue: 'default', payload: ['attempts' => 5]),
        ]);

        $failer->expects('find')->with('batch')->returns($jobs);
        $queue->expects('pushRaw')->with(Argument::satisfies(function ($payload) {
            return json_decode($payload, true)['attempts'] === 0;
        }), 'default', []);
        $failer->expects('forget')->with('job-1');

        $this->runRetryCommand(['id' => ['batch']], $failer, ['database' => $queue]);
    }

    public function testRefreshesTheRetryUntilTimestampWhenRetryingACollectionOfJobs()
    {
        $failer = Double::for(FailedJobProviderInterface::class);
        $queue = Double::for(QueueContract::class);

        $jobs = new Collection([
            'job-1' => $this->failedJob(
                id: 'job-1',
                connection: 'database',
                queue: 'default',
                payload: ['retryUntil' => 0],
                job: new QueueRetryCommandTestJobWithRetryUntil(retryUntil: 1234567890)
            ),
        ]);

        $failer->expects('find')->with('batch')->returns($jobs);
        $queue->expects('pushRaw')->with(Argument::satisfies(function ($payload) {
            return json_decode($payload, true)['retryUntil'] === 1234567890;
        }), 'default', []);
        $failer->expects('forget')->with('job-1');

        $this->runRetryCommand(['id' => ['batch']], $failer, ['database' => $queue]);
    }

    public function testDisplaysErrorWhenTheGivenIdResolvesToAnEmptyCollection()
    {
        $failer = Double::for(FailedJobProviderInterface::class);
        $failer->expects('find')->with('batch')->returns(new Collection);

        $output = $this->runRetryCommand(['id' => ['batch']], $failer, []);

        $this->assertStringContainsString('Pushing failed queue jobs back onto the queue.', $output);
        $this->assertStringContainsString('Unable to find any failed jobs with ID [batch].', $output);
        $this->assertStringNotContainsString('No retryable jobs found.', $output);
    }

    public function testDisplaysErrorWhenTheGivenIdResolvesToAnEmptyLazyCollection()
    {
        $failer = Double::for(FailedJobProviderInterface::class);

        $resolved = false;

        $jobs = new LazyCollection(function () use (&$resolved) {
            $resolved = true;

            yield from [];
        });

        $failer->expects('find')->with('batch')->returns($jobs);

        $output = $this->runRetryCommand(['id' => ['batch']], $failer, []);

        $this->assertTrue($resolved);
        $this->assertStringContainsString('Unable to find any failed jobs with ID [batch].', $output);
        $this->assertStringNotContainsString('No retryable jobs found.', $output);
    }

    public function testContinuesRetryingRemainingJobsAfterAJobIsNotFound()
    {
        $failer = Double::for(FailedJobProviderInterface::class);
        $queue = Double::for(QueueContract::class);

        $failer->expects('find')->with('1')->returns(null);

        $failer->expects('find')->with('2')->returns($this->failedJob(id: '2', connection: 'database', queue: 'default'));
        $queue->expects('pushRaw')->with($this->retriedPayload(id: '2'), 'default', []);
        $failer->expects('forget')->with(2);

        $output = $this->runRetryCommand(['id' => ['1', '2']], $failer, ['database' => $queue]);

        $this->assertStringContainsString('Unable to find failed job with ID [1].', $output);
        $this->assertStringContainsString('DONE', $output);
    }

    public function testContinuesRetryingRemainingJobsAfterAnIdResolvesToAnEmptyCollection()
    {
        $failer = Double::for(FailedJobProviderInterface::class);
        $queue = Double::for(QueueContract::class);

        $failer->expects('find')->with('batch')->returns(new Collection);

        $failer->expects('find')->with('2')->returns($this->failedJob(id: '2', connection: 'database', queue: 'default'));
        $queue->expects('pushRaw')->with($this->retriedPayload(id: '2'), 'default', []);
        $failer->expects('forget')->with(2);

        $output = $this->runRetryCommand(['id' => ['batch', '2']], $failer, ['database' => $queue]);

        $this->assertStringContainsString('Unable to find any failed jobs with ID [batch].', $output);
        $this->assertStringContainsString('DONE', $output);
    }

    public function testRetriesAMixtureOfSingleJobsAndCollectionsOfJobs()
    {
        $failer = Double::for(FailedJobProviderInterface::class);
        $queue = Double::for(QueueContract::class);

        $failer->expects('find')->with('batch')->returns(new Collection([
            'job-1' => $this->failedJob(id: 'job-1', connection: 'database', queue: 'default'),
            'job-2' => $this->failedJob(id: 'job-2', connection: 'database', queue: 'default'),
        ]));

        $failer->expects('find')->with('9')->returns($this->failedJob(id: '9', connection: 'database', queue: 'default'));

        $queue->expects('pushRaw')->with($this->retriedPayload(id: 'job-1'), 'default', []);
        $failer->expects('forget')->with('job-1');

        $queue->expects('pushRaw')->with($this->retriedPayload(id: 'job-2'), 'default', []);
        $failer->expects('forget')->with('job-2');

        $queue->expects('pushRaw')->with($this->retriedPayload(id: '9'), 'default', []);
        $failer->expects('forget')->with(9);

        $this->runRetryCommand(['id' => ['batch', '9']], $failer, ['database' => $queue]);
    }

    public function testForgetsJobsUsingTheCollectionKeyRatherThanTheJobId()
    {
        $failer = Double::for(FailedJobProviderInterface::class);
        $queue = Double::for(QueueContract::class);

        $key = 'https://cloud.test/failed-jobs/batch-1:job-1';

        $failer->expects('find')->with('batch')->returns(new Collection([
            $key => $this->failedJob(id: 'job-1', connection: 'database', queue: 'default'),
        ]));

        $queue->expects('pushRaw')->with($this->retriedPayload(id: 'job-1'), 'default', []);
        $failer->expects('forget')->with($key);

        $output = $this->runRetryCommand(['id' => ['batch']], $failer, ['database' => $queue]);

        $this->assertMatchesRegularExpression('/^  '.preg_quote($key, '/').' \.+/m', $output);
    }

    public function testForgetsJobsUsingTheCollectionKeyWhenTheCollectionIsNotKeyed()
    {
        $failer = Double::for(FailedJobProviderInterface::class);
        $queue = Double::for(QueueContract::class);

        $failer->expects('find')->with('batch')->returns(new Collection([
            $this->failedJob(id: 'job-1', connection: 'database', queue: 'default'),
            $this->failedJob(id: 'job-2', connection: 'database', queue: 'default'),
        ]));

        $queue->expects('pushRaw')->with($this->retriedPayload(id: 'job-1'), 'default', []);
        $failer->expects('forget')->with(0);

        $queue->expects('pushRaw')->with($this->retriedPayload(id: 'job-2'), 'default', []);
        $failer->expects('forget')->with(1);

        $this->runRetryCommand(['id' => ['batch']], $failer, ['database' => $queue]);
    }

    public function testDispatchesRetryRequestedEventForEveryJobInACollection()
    {
        $failer = Double::for(FailedJobProviderInterface::class);
        $queue = Double::for(QueueContract::class);
        $events = Double::for(Dispatcher::class);

        $jobs = new Collection([
            'job-1' => $this->failedJob(id: 'job-1', connection: 'database', queue: 'default'),
            'job-2' => $this->failedJob(id: 'job-2', connection: 'database', queue: 'default'),
        ]);

        $failer->expects('find')->with('batch')->returns($jobs);

        $dispatched = [];

        $events->expects('dispatch')->times(2)->with(Argument::type(JobRetryRequested::class))->resolves(function ($event) use (&$dispatched) {
            $dispatched[] = $event->job->id;
        });

        $queue->expects('pushRaw')->times(2);
        $failer->expects('forget')->with('job-1');
        $failer->expects('forget')->with('job-2');

        $this->runRetryCommand(['id' => ['batch']], $failer, ['database' => $queue], $events);

        $this->assertSame(['job-1', 'job-2'], $dispatched);
    }

    public function testStopsRetryingWhenAJobInACollectionFailsToBePushed()
    {
        $failer = Double::for(FailedJobProviderInterface::class);
        $queue = Double::for(QueueContract::class);

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

        $failer->expects('find')->with('batch')->returns($jobs);

        $queue->expects('pushRaw')->with($this->retriedPayload(id: 'job-1'), 'default', []);
        $failer->expects('forget')->with('job-1');

        $queue->expects('pushRaw')->with($this->retriedPayload(id: 'job-2'), 'default', [])->throws(new RuntimeException('Unable to push job.'));
        $failer->expects('forget')->with('job-2')->never();

        $queue->expects('pushRaw')->with($this->retriedPayload(id: 'job-3'), 'default', [])->never();
        $failer->expects('forget')->with('job-3')->never();

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
        $failer = Double::for(FailedJobProviderInterface::class);
        $queue = Double::for(QueueContract::class);
        $events = Double::for(Dispatcher::class);

        $jobs = new Collection([
            'job-1' => $this->failedJob(id: 'job-1', connection: 'database', queue: 'default'),
        ]);

        $failer->expects('find')->with('batch')->returns($jobs);

        $sequence = [];

        $events->expects('dispatch')->with(Argument::type(JobRetryRequested::class))->resolves(function () use (&$sequence) {
            $sequence[] = 'dispatch';
        });

        $queue->expects('pushRaw')->with($this->retriedPayload(id: 'job-1'), 'default', [])->resolves(function () use (&$sequence) {
            $sequence[] = 'push';
        });

        $failer->expects('forget')->with('job-1')->resolves(function () use (&$sequence) {
            $sequence[] = 'forget';
        });

        $this->runRetryCommand(['id' => ['batch']], $failer, ['database' => $queue], $events);

        $this->assertSame(['dispatch', 'push', 'forget'], $sequence);
    }

    public function testOutputsAnEntryForEveryJobInACollection()
    {
        $failer = Double::for(FailedJobProviderInterface::class);
        $queue = Double::for(QueueContract::class);

        $jobs = new Collection([
            'job-1' => $this->failedJob(id: 'job-1', connection: 'database', queue: 'default'),
            'job-2' => $this->failedJob(id: 'job-2', connection: 'database', queue: 'default'),
        ]);

        $failer->expects('find')->with('batch')->returns($jobs);

        $queue->expects('pushRaw')->times(2);
        $failer->expects('forget')->with('job-1');
        $failer->expects('forget')->with('job-2');

        $output = $this->runRetryCommand(['id' => ['batch']], $failer, ['database' => $queue]);

        $this->assertSame(1, substr_count($output, 'Pushing failed queue jobs back onto the queue.'));
        $this->assertSame(1, substr_count($output, 'job-1'));
        $this->assertSame(1, substr_count($output, 'job-2'));
        $this->assertSame(2, substr_count($output, 'DONE'));
    }

    public function testRetriesCollectionsOfJobsWhenRetryingAllFailedJobs()
    {
        $failer = Double::for(FailedJobProviderInterface::class);
        $queue = Double::for(QueueContract::class);

        $failer->expects('ids')->with(Argument::none())->returns(['batch-1', 'batch-2']);

        $failer->expects('find')->with('batch-1')->returns(new Collection([
            'job-1' => $this->failedJob(id: 'job-1', connection: 'database', queue: 'default'),
        ]));

        $failer->expects('find')->with('batch-2')->returns(new Collection([
            'job-2' => $this->failedJob(id: 'job-2', connection: 'database', queue: 'emails'),
        ]));

        $queue->expects('pushRaw')->with($this->retriedPayload(id: 'job-1'), 'default', []);
        $failer->expects('forget')->with('job-1');

        $queue->expects('pushRaw')->with($this->retriedPayload(id: 'job-2'), 'emails', []);
        $failer->expects('forget')->with('job-2');

        $this->runRetryCommand(['id' => ['all']], $failer, ['database' => $queue]);
    }

    public function testRetriesTheSameJobTwiceWhenItAppearsInTwoCollections()
    {
        $failer = Double::for(FailedJobProviderInterface::class);
        $queue = Double::for(QueueContract::class);

        $failer->expects('find')->with('batch-1')->returns(new Collection([
            'job-1' => $this->failedJob(id: 'job-1', connection: 'database', queue: 'default'),
        ]));

        $failer->expects('find')->with('batch-2')->returns(new Collection([
            'job-1' => $this->failedJob(id: 'job-1', connection: 'database', queue: 'default'),
        ]));

        $queue->expects('pushRaw')->times(2)->with($this->retriedPayload(id: 'job-1'), 'default', []);
        $failer->expects('forget')->times(2)->with('job-1');

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

        $manager = Double::for(QueueManager::class);

        foreach ($connections as $name => $queue) {
            $manager->allows('connection')->with($name)->returns($queue);
        }

        $container->instance('queue', $manager);

        if (is_null($events)) {
            $events = Double::for(Dispatcher::class);
            $events->allows('dispatch');
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
