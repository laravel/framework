<?php

namespace Tests\Tests\Foundation;

use Aws\CommandInterface;
use Aws\Exception\AwsException;
use Aws\HandlerList;
use Aws\MockHandler;
use Aws\Result;
use Aws\Sqs\SqsClient;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\LostConnectionDetector;
use Illuminate\Foundation\Cloud;
use Illuminate\Foundation\Cloud\AgentAwareLostConnectionDetector;
use Illuminate\Foundation\Cloud\AgentUnreachableException;
use Illuminate\Foundation\Cloud\CloudJob;
use Illuminate\Foundation\Cloud\Events;
use Illuminate\Foundation\Cloud\FailedJobProvider;
use Illuminate\Foundation\Cloud\ManagedQueueNotFoundException;
use Illuminate\Foundation\Cloud\Queue;
use Illuminate\Foundation\Cloud\QueueConnector;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\Connectors\ConnectorInterface;
use Illuminate\Queue\Connectors\SqsConnector;
use Illuminate\Queue\Events\WorkerStopping;
use Illuminate\Queue\Failed\FileFailedJobProvider;
use Illuminate\Queue\Jobs\FakeJob;
use Illuminate\Queue\Jobs\SqsJob;
use Illuminate\Queue\SqsQueue;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerStopReason;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Testing\Fakes\QueueFake;
use InvalidArgumentException;
use Mockery\MockInterface;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Throwable;

#[WithMigration]
#[WithMigration('laravel', 'queue')]
class QueueTest extends TestCase
{
    use DatabaseMigrations;

    protected function defineEnvironment($app)
    {
        $app['config']->set('app.key', Str::random(32));
    }

    protected function setUp(): void
    {
        Worker::$restartable = true;
        Worker::$pausable = true;
        $_SERVER['LARAVEL_CLOUD'] = '1';
        $_SERVER['LARAVEL_CLOUD_MANAGED_QUEUES_CONFIG'] = json_encode([
            'driver' => 'cloud',
            'connection' => [
                'driver' => 'sqs',
                'region' => 'us-east-2',
                'prefix' => 'https://sqs.us-east-2.amazonaws.com/1234567',
                'suffix' => '-env-8280cf2c-2081-47e8-a1f1-9cdfcba8618f',
                'queue' => 'default',
            ],
        ]);

        parent::setUp();

        $this->app['config']->set('queue.connections.cloud', json_decode($_SERVER['LARAVEL_CLOUD_MANAGED_QUEUES_CONFIG'], true));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($_SERVER['LARAVEL_CLOUD'], $_SERVER['LARAVEL_CLOUD_MANAGED_QUEUES_CONFIG']);
        Worker::$restartable = true;
        Worker::$pausable = true;
    }

    public function testItDisablesQueueRestartPollingForManagedQueues()
    {
        $argv = $_SERVER['argv'];
        $_SERVER['argv'] = ['artisan', 'queue:work'];

        try {
            Cloud::bootManagedQueues($this->app);
            $this->assertTrue(Worker::$restartable);

            $this->app['queue']->connection('cloud');
            $this->assertFalse(Worker::$restartable);
        } finally {
            $_SERVER['argv'] = $argv;
        }
    }

    public function testItDisablesQueuePausePollingForManagedQueues()
    {
        $argv = $_SERVER['argv'];
        $_SERVER['argv'] = ['artisan', 'queue:work'];

        try {
            Cloud::bootManagedQueues($this->app);
            $this->assertTrue(Worker::$pausable);

            $this->app['queue']->connection('cloud');
            $this->assertFalse(Worker::$pausable);
        } finally {
            $_SERVER['argv'] = $argv;
        }
    }

    public function testItConfiguresCloudConnectionFromManagedQueuesConfig()
    {
        $this->app['config']->set('queue.connections.cloud', null);

        Cloud::configureManagedQueues($this->app);

        $expected = json_decode($_SERVER['LARAVEL_CLOUD_MANAGED_QUEUES_CONFIG'], true);
        $expected['connection']['after_commit'] = false;
        $expected['connection']['overflow'] = [
            'enabled' => false,
            'store' => null,
            'always' => false,
            'delete_after_processing' => true,
        ];

        $this->assertSame(
            $expected,
            $this->app['config']->get('queue.connections.cloud'),
        );
    }

    public function testItDoesNotConfigureManagedQueuesWhenNotEnabled()
    {
        unset($_SERVER['LARAVEL_CLOUD_MANAGED_QUEUES_CONFIG']);
        $this->app['config']->set('queue.connections.cloud', null);

        Cloud::configureManagedQueues($this->app);

        $this->assertNull($this->app['config']->get('queue.connections.cloud'));
    }

    public function testItBindsQueueConnectorAndNewsUpSqsConnector()
    {
        $this->app->bind(SqsConnector::class, fn () => throw new RuntimeException('Should not be resolved'));
        Cloud::bootManagedQueues($this->app);

        $this->app[QueueConnector::class];
    }

    public function testItBindsCloudQueue()
    {
        Cloud::bootManagedQueues($this->app);

        $this->assertInstanceOf(Queue::class, $this->app['queue']->connection('cloud'));
    }

    public function testItBindsCloudEventsAsSingleton()
    {
        Cloud::bootManagedQueues($this->app);

        $this->assertFalse($this->app->resolved(Events::class));
        $this->assertSame($this->app[Events::class], $this->app[Events::class]);
    }

    public function testItBindsTheQueueFailer()
    {
        Cloud::bootManagedQueues($this->app);

        $this->assertInstanceOf(FailedJobProvider::class, $this->app['queue.failer']);
    }

    public function testItDoesNotRegisterCloudConnectorWhenCloudQueueConnectionIsNotConfigured()
    {
        $this->app['config']->set('queue.connections.cloud', null);

        Cloud::bootManagedQueues($this->app);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The [cloud] queue connection has not been configured.');
        $this->app['queue']->connection('cloud');
    }

    public function testItDoesNotRegisterCloudConnectorWhenCloudQueueConnectionDriverIsNotCloud()
    {
        $this->app['config']->set('queue.connections.cloud.driver', 'sqs');
        $originalFailer = $this->app['queue.failer'];

        Cloud::bootManagedQueues($this->app);

        $this->assertFalse($this->app->bound(Events::class));
        $this->assertSame($originalFailer, $this->app['queue.failer']);
    }

    public function testItDoesNotEmitEventsWhilePoppingWhenNoJobsAreProcessingAndNoJobsAreAvailableToPop()
    {
        $eventsFake = $this->fakeEvents();
        [$queue] = $this->fakeQueue();

        $queue->pop();

        $this->assertSame([], $eventsFake->emitted);
    }

    public function testItEmitsStartedEventWhenJobIsSuccessfullyPopped()
    {
        $this->travelTo('2000-01-02 03:04:05.060708');
        $eventsFake = $this->fakeEvents();
        [$queue, $agent] = $this->fakeQueue();

        $agent->pushJob();
        $queue->pop();

        $this->assertSame([[
            '_cloud_event' => 'queue',
            'timestamp' => '2000-01-02 03:04:05.060708',
            'type' => 'started',
            'queue' => 'default',
        ]], $eventsFake->emitted);
    }

    public function testItEmitsProcessedEventWhenNextJobIsAboutToPop()
    {
        $this->travelTo('2000-01-02 03:04:05.060708');
        $eventsFake = $this->fakeEvents();
        [$queue, $agent] = $this->fakeQueue();

        $agent->pushJob();
        $queue->pop();
        $this->travel(1)->second();
        $queue->pop();

        $this->assertSame([
            [
                '_cloud_event' => 'queue',
                'timestamp' => '2000-01-02 03:04:05.060708',
                'type' => 'started',
                'queue' => 'default',
            ],
            [
                '_cloud_event' => 'queue',
                'timestamp' => '2000-01-02 03:04:06.060708',
                'type' => 'processed',
                'queue' => 'default',
                'duration_ms' => 1000,
            ],
        ], $eventsFake->emitted);
    }

    public function testItDoesNotEmitEventsForTheSameJobAfterItHasBeenProcessed()
    {
        $this->travelTo('2000-01-02 03:04:05.060708');
        $eventsFake = $this->fakeEvents();
        [$queue, $agent] = $this->fakeQueue();

        $agent->pushJob();
        $queue->pop();
        $queue->pop();
        $queue->pop();
        $queue->pop();

        $this->assertCount(2, $eventsFake->emitted);
    }

    public function testItRemembersTheQueueForTheProcessedEvent()
    {
        $this->travelTo('2000-01-02 03:04:05.060708');
        $eventsFake = $this->fakeEvents();
        [$queue, $agent] = $this->fakeQueue();

        $agent->pushJob();
        $agent->pushJob();
        $queue->pop('first');
        $queue->pop('second');
        $queue->pop('third');

        $this->assertSame([
            [
                '_cloud_event' => 'queue',
                'timestamp' => '2000-01-02 03:04:05.060708',
                'type' => 'started',
                'queue' => 'first',
            ],
            [
                '_cloud_event' => 'queue',
                'timestamp' => '2000-01-02 03:04:05.060708',
                'type' => 'processed',
                'queue' => 'first',
                'duration_ms' => 0,
            ], [
                '_cloud_event' => 'queue',
                'timestamp' => '2000-01-02 03:04:05.060708',
                'type' => 'started',
                'queue' => 'second',
            ],
            [
                '_cloud_event' => 'queue',
                'timestamp' => '2000-01-02 03:04:05.060708',
                'type' => 'processed',
                'queue' => 'second',
                'duration_ms' => 0,
            ],
        ], $eventsFake->emitted);
    }

    public function testItEmitsFailedJobEvents()
    {
        $this->travelTo('2000-01-02 03:04:05.060708');
        $eventsFake = $this->fakeEvents();
        [$queue, $agent] = $this->fakeQueue();
        $failerFake = $this->fakeFailer();
        $failedJobProvider = new FailedJobProvider($failerFake, $eventsFake, $this->app['encrypter']);
        $failedJobProvider->setQueue($queue);
        $this->app[FailedJobProvider::class] = $failedJobProvider;

        $agent->pushJob();
        $job = $queue->pop();
        $job->fail();
        Str::createUuidsUsingSequence([Uuid::fromString('00dc709e-90c4-70c2-87c8-9b7127d20e8f')]);
        $failedJobProvider->log('cloud', 'default', ['payload' => 'here'], new RuntimeException('Whoops!'));
        Str::createUuidsNormally();
        $queue->pop();

        unset($eventsFake->emitted[1]['exception']);
        $this->assertSame([
            [
                '_cloud_event' => 'queue',
                'timestamp' => '2000-01-02 03:04:05.060708',
                'type' => 'started',
                'queue' => 'default',
            ],
            [
                '_cloud_event' => 'failed_job',
                'id' => '00dc709e-90c4-70c2-87c8-9b7127d20e8f',
                'queue' => 'default',
                'started_at' => '2000-01-02 03:04:05.060708',
                'attempts' => 1,
                'payload' => [
                    'payload' => 'here',
                ],
            ],
            [
                '_cloud_event' => 'queue',
                'timestamp' => '2000-01-02 03:04:05.060708',
                'type' => 'failed',
                'queue' => 'default',
                'duration_ms' => 0,
            ],
        ], $eventsFake->emitted);
    }

    public function testItEmitsReleasedJobEvents()
    {
        $this->travelTo('2000-01-02 03:04:05.060708');
        $eventsFake = $this->fakeEvents();
        [$queue, $agent] = $this->fakeQueue();

        $agent->pushJob();
        $job = $queue->pop();
        $job->release();
        $queue->pop();

        $this->assertSame([
            [
                '_cloud_event' => 'queue',
                'timestamp' => '2000-01-02 03:04:05.060708',
                'type' => 'started',
                'queue' => 'default',
            ],
            [
                '_cloud_event' => 'queue',
                'timestamp' => '2000-01-02 03:04:05.060708',
                'type' => 'released',
                'queue' => 'default',
                'duration_ms' => 0,
            ],
        ], $eventsFake->emitted);
    }

    public function testPopReturnsACloudJobBuiltFromTheAgentResponse()
    {
        $this->fakeEvents();
        [$queue, $agent] = $this->fakeQueue();

        $agent->pushJob(['messageId' => 'message-id', 'body' => 'job-body']);

        $job = $queue->pop();

        $this->assertInstanceOf(CloudJob::class, $job);
        $this->assertSame('message-id', $job->getJobId());
        $this->assertSame('job-body', $job->getRawBody());
    }

    public function testPopReturnsNullWhenTheAgentHasNoJob()
    {
        $this->fakeEvents();
        [$queue] = $this->fakeQueue();

        $this->assertNull($queue->pop());
    }

    public function testPopReceivesDirectlyFromSqsWhenTheAgentIsDisabled()
    {
        // With the agent disabled (the default) the queue receives from SQS.
        Http::fake();
        $this->fakeEvents();
        [$queue, $client] = $this->mockedQueue();

        $client->shouldReceive('receiveMessage')->once()->andReturn(new Result([
            'Messages' => [[
                'MessageId' => 'message-id',
                'ReceiptHandle' => 'receipt-handle',
                'Body' => 'job-body',
                'Attributes' => ['ApproximateReceiveCount' => 1],
            ]],
        ]));

        $job = $queue->pop();

        $this->assertInstanceOf(SqsJob::class, $job);
        $this->assertNotInstanceOf(CloudJob::class, $job);
        $this->assertSame('message-id', $job->getJobId());
        $this->assertSame('job-body', $job->getRawBody());
        Http::assertNothingSent();
    }

    public function testPopReturnsNullWhenSqsHasNoMessageAndTheAgentIsDisabled()
    {
        $this->fakeEvents();
        [$queue, $client] = $this->mockedQueue();

        $client->shouldReceive('receiveMessage')->once()->andReturn(new Result(['Messages' => null]));

        $this->assertNull($queue->pop());
    }

    public function testDeletingAJobReportsProcessedToTheAgentWithoutTouchingSqs()
    {
        $this->fakeEvents();
        [$queue, $agent] = $this->fakeQueue();
        $pushed = $agent->pushJob();

        $job = $queue->pop();
        $job->delete();

        $this->assertTrue($job->isDeleted());
        $this->assertAgentResults([
            ['messageId' => $pushed['messageId'], 'status' => 'processed'],
        ]);
    }

    public function testFailingAJobReportsProcessedExactlyOnce()
    {
        $this->fakeEvents();
        [$queue, $agent] = $this->fakeQueue();
        $pushed = $agent->pushJob();

        $job = $queue->pop();
        $job->fail(new RuntimeException('Whoops!'));

        // fail() routes through delete(), so it reports a single "processed".
        $this->assertTrue($job->hasFailed());
        $this->assertAgentResults([
            ['messageId' => $pushed['messageId'], 'status' => 'processed'],
        ]);
    }

    public function testReleasingAJobReportsReleasedWithTheDelayToTheAgent()
    {
        $this->fakeEvents();
        [$queue, $agent] = $this->fakeQueue();
        $pushed = $agent->pushJob();

        $job = $queue->pop();
        $job->release(30);

        $this->assertTrue($job->isReleased());
        $this->assertAgentResults([
            ['messageId' => $pushed['messageId'], 'status' => 'released', 'delay' => 30],
        ]);
    }

    public function testPopBuildsTheJobWithTheCloudConnectionName()
    {
        $this->fakeEvents();
        [$queue, $agent] = $this->fakeQueue();
        $agent->pushJob();

        $this->assertSame('cloud', $queue->pop()->getConnectionName());
    }

    public function testPopUsesTheReceiveCountSuppliedByTheAgent()
    {
        $this->fakeEvents();
        [$queue, $agent] = $this->fakeQueue();
        $agent->pushJob(['attributes' => ['ApproximateReceiveCount' => '5']]);

        $this->assertSame(5, $queue->pop()->attempts());
    }

    public function testPopToleratesANonStringBodyFromTheAgent()
    {
        $this->fakeEvents();
        [$queue, $agent] = $this->fakeQueue();
        $agent->pushJob(['body' => ['not' => 'a-string']]);

        $job = $queue->pop();

        $this->assertInstanceOf(CloudJob::class, $job);
        $this->assertSame('', $job->getRawBody());
    }

    public function testPopAcceptsAFalsyButValidMessageId()
    {
        $this->fakeEvents();
        [$queue, $agent] = $this->fakeQueue();
        // "0" is a valid, non-empty id that empty() would wrongly reject.
        $agent->pushJob(['messageId' => '0']);

        $this->assertSame('0', $queue->pop()->getJobId());
    }

    public function testPopResolvesOverflowPayloadsThroughTheCacheAndCleansUpOnDelete()
    {
        $this->fakeEvents();
        config(['queue.connections.cloud.connection.overflow' => [
            'enabled' => true,
            'store' => 'array',
            'delete_after_processing' => true,
        ]]);
        [$queue, $agent] = $this->fakeQueue();

        $payload = json_encode(['job' => MyJob::class, 'data' => ['resolved' => true]]);
        Cache::store('array')->put('overflow-pointer', $payload);

        $agent->pushJob(['body' => json_encode(['@pointer' => 'overflow-pointer'])]);

        $job = $queue->pop();

        // The payload is resolved from the cache, not the raw "@pointer" body.
        $this->assertSame($payload, $job->getRawBody());

        // Deleting still cleans up the cached payload.
        $job->delete();

        $this->assertNull(Cache::store('array')->get('overflow-pointer'));
    }

    public function testOverflowPayloadIsRetainedWhenTheProcessedReportFails()
    {
        $this->fakeEvents();
        config(['queue.connections.cloud.connection.overflow' => [
            'enabled' => true,
            'store' => 'array',
            'delete_after_processing' => true,
        ]]);
        [$queue, $agent] = $this->fakeQueue();

        // The agent rejects POST /result with a client error.
        $agent->resultStatus = 422;

        $payload = json_encode(['job' => MyJob::class, 'data' => ['resolved' => true]]);
        Cache::store('array')->put('overflow-pointer', $payload);

        $agent->pushJob(['body' => json_encode(['@pointer' => 'overflow-pointer'])]);

        $job = $queue->pop();

        // A rejected report throws, so delete() never reaches the overflow purge.
        try {
            $job->delete();
            $this->fail('Expected the rejected report to throw a RequestException.');
        } catch (RequestException) {
            //
        }

        // The unacknowledged payload is retained for the redelivered job.
        $this->assertSame($payload, Cache::store('array')->get('overflow-pointer'));
    }

    public function testDeletingPropagatesWhenTheAgentIsUnreachable()
    {
        $this->fakeEvents();
        $sqs = $this->mock(SqsClient::class);
        [$queue, $agent] = $this->fakeQueue($sqs);
        $agent->pushJob();

        $job = $queue->pop();

        // An unreachable agent propagates rather than deleting from SQS directly.
        $agent->resultUnreachable = true;
        $sqs->shouldNotReceive('deleteMessage');

        $this->expectException(AgentUnreachableException::class);

        $job->delete();
    }

    public function testReleasingPropagatesWhenTheAgentIsUnreachable()
    {
        $this->fakeEvents();
        $sqs = $this->mock(SqsClient::class);
        [$queue, $agent] = $this->fakeQueue($sqs);
        $agent->pushJob();

        $job = $queue->pop();

        // An unreachable agent propagates rather than resetting visibility on SQS.
        $agent->resultUnreachable = true;
        $sqs->shouldNotReceive('changeMessageVisibility');

        $this->expectException(AgentUnreachableException::class);

        $job->release(30);
    }

    public function testReportingThrowsWhenTheAgentRejectsTheResult()
    {
        $this->fakeEvents();
        $sqs = $this->mock(SqsClient::class);
        [$queue, $agent] = $this->fakeQueue($sqs);
        $agent->pushJob();

        $job = $queue->pop();

        // A client-error rejection is message-specific, so it propagates as a
        // RequestException for the worker to report rather than deleting from
        // SQS directly or restarting the pod.
        $agent->resultStatus = 422;
        $sqs->shouldNotReceive('deleteMessage');

        $this->expectException(RequestException::class);

        $job->delete();
    }

    public function testReportingEscalatesWhenTheAgentReturnsAServerError()
    {
        $this->fakeEvents();
        $sqs = $this->mock(SqsClient::class);
        [$queue, $agent] = $this->fakeQueue($sqs);
        $agent->pushJob();

        $job = $queue->pop();

        // A server error means the agent itself is wedged, so it escalates as
        // an unreachable fault to restart the pod rather than deleting from SQS.
        $agent->resultStatus = 500;
        $sqs->shouldNotReceive('deleteMessage');

        $this->expectException(AgentUnreachableException::class);

        $job->delete();
    }

    public function testPopThrowsWhenTheAgentReturnsAnError()
    {
        $this->fakeEvents();
        [$queue, $agent] = $this->fakeQueue();

        // Drive the agent's own GET /next stub so the non-200 branch is hit;
        // a separate Http::fake() would be shadowed by the agent closure.
        $agent->nextResponse = Http::response('error', 500);

        // An error status means the agent cannot serve work, so it escalates
        // as an unrecoverable fault rather than idling and re-polling forever.
        $this->expectException(AgentUnreachableException::class);

        $queue->pop();
    }

    public function testPopThrowsWhenTheAgentReturnsAnUnexpectedSuccessStatus()
    {
        $this->fakeEvents();
        [$queue, $agent] = $this->fakeQueue();

        // The agent only ever answers 200 (job) or 204 (empty); any other 2xx
        // is off-contract, so it escalates rather than being decoded as a job.
        $agent->nextResponse = Http::response('', 202);

        $this->expectException(AgentUnreachableException::class);

        $queue->pop();
    }

    public function testPopThrowsWhenTheAgentReturnsANonArrayBody()
    {
        $this->fakeEvents();
        [$queue, $agent] = $this->fakeQueue();

        // A 200 that decodes to a scalar is an agent fault; treat it the same
        // as an unreachable agent rather than idling.
        $agent->nextResponse = Http::response('"not-an-array"', 200);

        $this->expectException(AgentUnreachableException::class);

        $queue->pop();
    }

    public function testPopThrowsWhenTheAgentSocketIsUnreachable()
    {
        $this->fakeEvents();
        [$queue] = $this->fakeQueue();

        // An unreachable socket must escalate as an unrecoverable fault, not idle.
        Http::fake(fn () => throw new ConnectionException('Connection refused'));

        $this->expectException(AgentUnreachableException::class);

        $queue->pop();
    }

    public function testAgentAwareDetectorTreatsAnUnreachableAgentAsALostConnection()
    {
        $detector = new AgentAwareLostConnectionDetector(new LostConnectionDetector);

        // An unreachable agent is treated as a lost connection so the worker exits.
        $this->assertTrue($detector->causedByLostConnection(new AgentUnreachableException));

        // Everything else is delegated to the wrapped detector untouched.
        $this->assertFalse($detector->causedByLostConnection(new \RuntimeException('boom')));
        $this->assertTrue($detector->causedByLostConnection(new \RuntimeException('server has gone away')));
    }

    public function testReleasingThenFailingReportsBothOutcomesToTheAgent()
    {
        $this->fakeEvents();
        [$queue, $agent] = $this->fakeQueue();
        $pushed = $agent->pushJob();

        $job = $queue->pop();
        $job->release(30);
        $job->fail(new RuntimeException('Whoops!'));

        // CloudJob keeps no memory of prior reports, so both outcomes are
        // reported in order; reconciling them is the agent's responsibility.
        $this->assertAgentResults([
            ['messageId' => $pushed['messageId'], 'status' => 'released', 'delay' => 30],
            ['messageId' => $pushed['messageId'], 'status' => 'processed'],
        ]);
    }

    public function testItEmitsJobQueuedEvent()
    {
        $this->travelTo('2000-01-02 03:04:05.060708');
        Cloud::configureManagedQueues($this->app);
        Cloud::bootManagedQueues($this->app);
        $eventsFake = $this->fakeEvents();
        [$queue, $client] = $this->mockedQueue();
        $client->shouldReceive('sendMessage')->times(7)->andReturn(new Result());

        $queue->push(new FakeJob, queue: '1');
        $queue->pushOn('2', new FakeJob);
        $queue->pushRaw('', queue: '3');
        $queue->later(1, new FakeJob, queue: '4');
        $queue->laterOn('5', 1, new FakeJob);
        $queue->bulk([new FakeJob, new FakeJob], queue: '6');

        $this->assertSame([
            [
                '_cloud_event' => 'queue',
                'timestamp' => '2000-01-02 03:04:05.060708',
                'type' => 'queued',
                'queue' => '1',
            ],
            [
                '_cloud_event' => 'queue',
                'timestamp' => '2000-01-02 03:04:05.060708',
                'type' => 'queued',
                'queue' => '2',
            ],
            [
                '_cloud_event' => 'queue',
                'timestamp' => '2000-01-02 03:04:05.060708',
                'type' => 'queued',
                'queue' => '3',
            ],
            [
                '_cloud_event' => 'queue',
                'timestamp' => '2000-01-02 03:04:05.060708',
                'type' => 'queued',
                'queue' => '4',
            ],
            [
                '_cloud_event' => 'queue',
                'timestamp' => '2000-01-02 03:04:05.060708',
                'type' => 'queued',
                'queue' => '5',
            ],
            [
                '_cloud_event' => 'queue',
                'timestamp' => '2000-01-02 03:04:05.060708',
                'type' => 'queued',
                'queue' => '6',
            ],
            [
                '_cloud_event' => 'queue',
                'timestamp' => '2000-01-02 03:04:05.060708',
                'type' => 'queued',
                'queue' => '6',
            ],
        ], $eventsFake->emitted);
    }

    public function testItEmitsReleasedEventWhenWorkerStopsBecauseItTimedOut()
    {
        $argv = $_SERVER['argv'];
        $_SERVER['argv'] = ['artisan', 'queue:work'];

        try {
            $this->travelTo('2000-01-02 03:04:05.060708');
            Cloud::configureManagedQueues($this->app);
            Cloud::bootManagedQueues($this->app);
            $eventsFake = $this->fakeEvents();
            [$queue, $agent] = $this->fakeQueue();

            $agent->pushJob();
            $queue->pop();
            $this->travel(2)->seconds();

            $this->app['events']->dispatch(new WorkerStopping(0, null, WorkerStopReason::TimedOut));

            $this->assertSame([
                [
                    '_cloud_event' => 'queue',
                    'timestamp' => '2000-01-02 03:04:05.060708',
                    'type' => 'started',
                    'queue' => 'default',
                ],
                [
                    '_cloud_event' => 'queue',
                    'timestamp' => '2000-01-02 03:04:07.060708',
                    'type' => 'released',
                    'queue' => 'default',
                    'duration_ms' => 2000,
                ],
            ], $eventsFake->emitted);
        } finally {
            $_SERVER['argv'] = $argv;
        }
    }

    public function testItEmitsProcessedEventWhenWorkerStopsForReasonsOtherThanTimedOut()
    {
        $argv = $_SERVER['argv'];
        $_SERVER['argv'] = ['artisan', 'queue:work'];

        $reasons = [
            WorkerStopReason::Interrupted,
            WorkerStopReason::LostConnection,
            WorkerStopReason::MaxJobsExceeded,
            WorkerStopReason::MaxMemoryExceeded,
            WorkerStopReason::MaxTimeExceeded,
            WorkerStopReason::QueueEmpty,
            WorkerStopReason::ReceivedRestartSignal,
        ];

        try {
            $this->travelTo('2000-01-02 03:04:05.060708');
            Cloud::configureManagedQueues($this->app);
            Cloud::bootManagedQueues($this->app);
            $eventsFake = $this->fakeEvents();
            [$queue, $agent] = $this->fakeQueue();

            foreach ($reasons as $index => $reason) {
                $agent->pushJob();
                $queue->pop();

                $this->app['events']->dispatch(new WorkerStopping(0, null, $reason));

                $this->assertSame([
                    '_cloud_event' => 'queue',
                    'timestamp' => '2000-01-02 03:04:05.060708',
                    'type' => 'processed',
                    'queue' => 'default',
                    'duration_ms' => 0,
                ], $eventsFake->emitted[($index * 2) + 1]);
            }
        } finally {
            $_SERVER['argv'] = $argv;
        }
    }

    public function testItEmitsProcessedEventWhenWorkerStopsWithoutAReason()
    {
        $argv = $_SERVER['argv'];
        $_SERVER['argv'] = ['artisan', 'queue:work'];

        try {
            $this->travelTo('2000-01-02 03:04:05.060708');
            Cloud::configureManagedQueues($this->app);
            Cloud::bootManagedQueues($this->app);
            $eventsFake = $this->fakeEvents();
            [$queue, $agent] = $this->fakeQueue();

            $agent->pushJob();
            $queue->pop();

            $this->app['events']->dispatch(new WorkerStopping);

            $this->assertSame([
                [
                    '_cloud_event' => 'queue',
                    'timestamp' => '2000-01-02 03:04:05.060708',
                    'type' => 'started',
                    'queue' => 'default',
                ],
                [
                    '_cloud_event' => 'queue',
                    'timestamp' => '2000-01-02 03:04:05.060708',
                    'type' => 'processed',
                    'queue' => 'default',
                    'duration_ms' => 0,
                ],
            ], $eventsFake->emitted);
        } finally {
            $_SERVER['argv'] = $argv;
        }
    }

    public function testWorkerStoppingListenerEmitsFailedTypeWhenProcessingJobHasFailed()
    {
        $argv = $_SERVER['argv'];
        $_SERVER['argv'] = ['artisan', 'queue:work'];

        try {
            $this->travelTo('2000-01-02 03:04:05.060708');
            Cloud::configureManagedQueues($this->app);
            Cloud::bootManagedQueues($this->app);
            $eventsFake = $this->fakeEvents();
            [$queue, $agent] = $this->fakeQueue();

            $agent->pushJob();
            $job = $queue->pop();
            $job->fail();

            $this->app['events']->dispatch(new WorkerStopping(0, null, WorkerStopReason::TimedOut));

            $this->assertSame('failed', $eventsFake->emitted[1]['type']);
        } finally {
            $_SERVER['argv'] = $argv;
        }
    }

    public function testWorkerStoppingListenerEmitsReleasedTypeWhenProcessingJobWasReleased()
    {
        $argv = $_SERVER['argv'];
        $_SERVER['argv'] = ['artisan', 'queue:work'];

        try {
            $this->travelTo('2000-01-02 03:04:05.060708');
            Cloud::configureManagedQueues($this->app);
            Cloud::bootManagedQueues($this->app);
            $eventsFake = $this->fakeEvents();
            [$queue, $agent] = $this->fakeQueue();

            $agent->pushJob();
            $job = $queue->pop();
            $job->release();

            $this->app['events']->dispatch(new WorkerStopping(0, null, WorkerStopReason::MaxJobsExceeded));

            $this->assertSame('released', $eventsFake->emitted[1]['type']);
        } finally {
            $_SERVER['argv'] = $argv;
        }
    }

    public function testWorkerStoppingListenerDoesNothingWhenNoJobIsProcessing()
    {
        $argv = $_SERVER['argv'];
        $_SERVER['argv'] = ['artisan', 'queue:work'];

        try {
            Cloud::configureManagedQueues($this->app);
            Cloud::bootManagedQueues($this->app);
            $eventsFake = $this->fakeEvents();
            $this->fakeQueue();

            $this->app['events']->dispatch(new WorkerStopping(0, null, WorkerStopReason::TimedOut));
            $this->app['events']->dispatch(new WorkerStopping(0, null, WorkerStopReason::QueueEmpty));

            $this->assertSame([], $eventsFake->emitted);
        } finally {
            $_SERVER['argv'] = $argv;
        }
    }

    public function testItDoesNotRegisterWorkerStoppingListenerWhenNotRunningQueueWork()
    {
        $argv = $_SERVER['argv'];
        $_SERVER['argv'] = ['artisan', 'tinker'];

        try {
            $this->travelTo('2000-01-02 03:04:05.060708');
            Cloud::configureManagedQueues($this->app);
            Cloud::bootManagedQueues($this->app);
            $eventsFake = $this->fakeEvents();
            [$queue, $agent] = $this->fakeQueue();

            $agent->pushJob();
            $queue->pop();

            $this->app['events']->dispatch(new WorkerStopping(0, null, WorkerStopReason::TimedOut));

            $this->assertSame([
                [
                    '_cloud_event' => 'queue',
                    'timestamp' => '2000-01-02 03:04:05.060708',
                    'type' => 'started',
                    'queue' => 'default',
                ],
            ], $eventsFake->emitted);
        } finally {
            $_SERVER['argv'] = $argv;
        }
    }

    public function testItRespectsDispatchAfterTransaction()
    {
        $this->travelTo('2000-01-02 03:04:05.060708');
        Cloud::configureManagedQueues($this->app);
        Cloud::bootManagedQueues($this->app);
        $eventsFake = $this->fakeEvents();
        $this->app['config']->set('queue.connections.cloud.connection.after_commit', true);
        [$queue, $client] = $this->mockedQueue();
        $client->shouldReceive('sendMessage')->times(7)->andReturn(new Result());

        DB::beginTransaction();

        $queue->push(new FakeJob, queue: '1');
        $queue->pushOn('2', new FakeJob);
        $queue->pushRaw('', queue: '3');
        $queue->later(1, new FakeJob, queue: '4');
        $queue->laterOn('5', 1, new FakeJob);
        $queue->bulk([new FakeJob, new FakeJob], queue: '6');

        $this->travel(10)->minutes();
        DB::commit();

        $this->assertSame([
            [
                '_cloud_event' => 'queue',
                'timestamp' => '2000-01-02 03:04:05.060708',
                'type' => 'queued',
                'queue' => '3',
            ],
            [
                '_cloud_event' => 'queue',
                'timestamp' => '2000-01-02 03:14:05.060708',
                'type' => 'queued',
                'queue' => '1',
            ],
            [
                '_cloud_event' => 'queue',
                'timestamp' => '2000-01-02 03:14:05.060708',
                'type' => 'queued',
                'queue' => '2',
            ],
            [
                '_cloud_event' => 'queue',
                'timestamp' => '2000-01-02 03:14:05.060708',
                'type' => 'queued',
                'queue' => '4',
            ],
            [
                '_cloud_event' => 'queue',
                'timestamp' => '2000-01-02 03:14:05.060708',
                'type' => 'queued',
                'queue' => '5',
            ],
            [
                '_cloud_event' => 'queue',
                'timestamp' => '2000-01-02 03:14:05.060708',
                'type' => 'queued',
                'queue' => '6',
            ],
            [
                '_cloud_event' => 'queue',
                'timestamp' => '2000-01-02 03:14:05.060708',
                'type' => 'queued',
                'queue' => '6',
            ],
        ], $eventsFake->emitted);
    }

    public function testItCapturesDurationForMultipleJobs()
    {
        $this->travelTo('2000-01-02 03:04:05.060708');
        $eventsFake = $this->fakeEvents();
        [$queue, $agent] = $this->fakeQueue();

        $agent->pushJob();
        $agent->pushJob();
        $queue->pop();
        $this->travel(1)->second();
        $queue->pop();
        $this->travel(0.5)->second();
        $queue->pop();

        $this->assertSame(1000, $eventsFake->emitted[1]['duration_ms']);
        $this->assertSame(500, $eventsFake->emitted[3]['duration_ms']);
    }

    public function testItCapturesUtcTime()
    {
        date_default_timezone_set('Australia/Melbourne');
        $this->travelTo(Carbon::parse('2000-01-02 03:04:05.060708', 'Australia/Melbourne'));
        $eventsFake = $this->fakeEvents();
        [$queue, $agent] = $this->fakeQueue();

        $agent->pushJob();
        $queue->pop();
        $this->travel(1)->second();
        $queue->pop();

        $this->assertSame([
            [
                '_cloud_event' => 'queue',
                'timestamp' => '2000-01-01 16:04:05.060708',
                'type' => 'started',
                'queue' => 'default',
            ],
            [
                '_cloud_event' => 'queue',
                'timestamp' => '2000-01-01 16:04:06.060708',
                'type' => 'processed',
                'queue' => 'default',
                'duration_ms' => 1000,
            ],
        ], $eventsFake->emitted);
    }

    public function testFindProxiesToFailerForNonUrls()
    {
        $eventsFake = $this->fakeEvents();
        $failer = $this->fakeFailer();
        $provider = new FailedJobProvider($failer, $eventsFake, $this->app['encrypter']);

        $job = $provider->find('not-a-url');

        $this->assertNull($job);
    }

    public function testFindGetsUrlAndDecryptsResponse()
    {
        $eventsFake = $this->fakeEvents();
        $failer = $this->fakeFailer();
        $provider = new FailedJobProvider($failer, $eventsFake, $this->app['encrypter']);

        $payload = ['id' => 'test-job-id', 'connection' => 'cloud', 'queue' => 'default', 'payload' => '{"job":"App\\\\Jobs\\\\TestJob"}'];
        $encrypted = Crypt::encryptString(json_encode($payload));

        Http::fake([
            'https://cloud.laravel.com/*' => Http::response($encrypted),
        ]);

        $result = $provider->find('https://cloud.laravel.com/api/jobs/test-job-id?signature=abc');

        $this->assertIsObject($result);
        $this->assertSame('test-job-id', $result->id);
        $this->assertSame('cloud', $result->connection);
        $this->assertSame('default', $result->queue);
        $this->assertSame('{"job":"App\\\\Jobs\\\\TestJob"}', $result->payload);
        Http::assertSent(fn ($request) => $request->url() === 'https://cloud.laravel.com/api/jobs/test-job-id?signature=abc');
    }

    public function testFindReturnsNullWhenDecryptionFails()
    {
        $eventsFake = $this->fakeEvents();
        $failer = $this->fakeFailer();
        $provider = new FailedJobProvider($failer, $eventsFake, $this->app['encrypter']);

        Http::fake([
            'https://cloud.laravel.com/*' => Http::response('not-valid-encrypted-data'),
        ]);

        try {
            $provider->find('https://cloud.laravel.com/api/jobs/test-job-id?signature=abc');
            $this->fail();
        } catch (Throwable $e) {
            $this->assertInstanceOf(DecryptException::class, $e);
        }
    }

    public function testFindReturnsNullWhenHttpRequestFails()
    {
        $eventsFake = $this->fakeEvents();
        $failer = $this->fakeFailer();
        $provider = new FailedJobProvider($failer, $eventsFake, $this->app['encrypter']);

        Http::fake([
            'https://cloud.laravel.com/*' => Http::response('Server Error', 500),
        ]);

        try {
            $provider->find('https://cloud.laravel.com/api/jobs/test-job-id?signature=abc');
            $this->fail();
        } catch (Throwable $e) {
            $this->assertInstanceOf(RequestException::class, $e);
        }
    }

    public function testForgetProxiesToFailerForNonUrls()
    {
        $eventsFake = $this->fakeEvents();
        $failer = $this->fakeFailer();
        $provider = new FailedJobProvider($failer, $eventsFake, $this->app['encrypter']);

        // First log a job to the failer with a UUID
        $uuid = (string) Str::uuid();
        $failer->log('database', 'default', json_encode(['uuid' => $uuid]), new \Exception('test'));
        $jobId = $failer->ids()[0];

        // Forget should delegate to the underlying failer
        $result = $provider->forget($jobId);

        $this->assertTrue($result);
        $this->assertEmpty($failer->ids());
    }

    public function testForgetEmitsEventAfterFind()
    {
        $this->travelTo('2000-01-02 03:04:05.060708');
        $eventsFake = $this->fakeEvents();
        $failer = $this->fakeFailer();
        $provider = new FailedJobProvider($failer, $eventsFake, $this->app['encrypter']);

        $payload = ['id' => 'forget-test-id', 'connection' => 'cloud', 'queue' => 'default', 'payload' => '{}'];
        $encrypted = Crypt::encryptString(json_encode($payload));

        Http::fake([
            'https://cloud.laravel.com/*' => Http::response($encrypted),
        ]);

        $url = 'https://cloud.laravel.com/api/jobs/forget-test-id?signature=abc';
        $provider->find($url);
        $result = $provider->forget($url);

        $this->assertTrue($result);
        $this->assertSame([
            [
                '_cloud_event' => 'failed_job',
                'id' => 'forget-test-id',
                'queue' => 'default',
                'retried_at' => '2000-01-02 03:04:05.060708',
            ],
        ], $eventsFake->emitted);
    }

    public function testForgetReturnsFalseWithoutPriorFind()
    {
        $eventsFake = $this->fakeEvents();
        $failer = $this->fakeFailer();
        $provider = new FailedJobProvider($failer, $eventsFake, $this->app['encrypter']);

        $result = $provider->forget('https://cloud.laravel.com/api/jobs/some-id?signature=abc');

        $this->assertFalse($result);
        $this->assertEmpty($eventsFake->emitted);
    }

    public function testItThrowsManagedQueueNotFoundExceptionWhenQueueDoesNotExist()
    {
        Cloud::configureManagedQueues($this->app);
        Cloud::bootManagedQueues($this->app);
        $this->fakeEvents();

        $mock = new MockHandler();
        $mock->append(fn (CommandInterface $cmd) => new AwsException('Queue does not exist.', $cmd, [
            'code' => 'AWS.SimpleQueueService.NonExistentQueue',
        ]));

        $client = new SqsClient([
            'region' => 'us-east-2',
            'version' => 'latest',
            'handler' => $mock,
            'credentials' => false,
        ]);

        $this->app->instance(QueueConnector::class, new QueueConnector(new class($client) implements ConnectorInterface
        {
            public function __construct(private $client)
            {
            }

            public function connect($config)
            {
                return new SqsQueue(
                    $this->client,
                    $config['queue'],
                    $config['prefix'] ?? '',
                    $config['suffix'] ?? '',
                    $config['after_commit'] ?? null,
                    $config['overflow'] ?? [],
                );
            }
        }, $this->app));

        $this->app['queue']->addConnector('cloud', $this->app->factory(QueueConnector::class));

        $queue = $this->app['queue']->connection('cloud');

        $this->expectException(ManagedQueueNotFoundException::class);
        $this->expectExceptionMessage('Managed queue [missing-queue] does not exist.');

        $queue->push(new FakeJob, queue: 'missing-queue');
    }

    public function testItUsesConfigValuesToNormalizeQueueName()
    {
        Cloud::configureManagedQueues($this->app);
        Cloud::bootManagedQueues($this->app);
        $eventsFake = $this->fakeEvents();
        [$queue, $client] = $this->mockedQueue();
        $client->shouldReceive('sendMessage')->times(1)->andReturn(new Result());

        unset($_SERVER['SQS_PREFIX'], $_SERVER['SQS_SUFFIX']);

        $queue->push(new FakeJob, queue: 'https://sqs.us-east-2.amazonaws.com/1234567/my-queue-env-8280cf2c-2081-47e8-a1f1-9cdfcba8618f');

        $this->assertSame('my-queue', $eventsFake->emitted[0]['queue']);
    }

    public function testItNormalizesFifoQueueNamesWithoutLeakingTheSuffix()
    {
        Cloud::configureManagedQueues($this->app);
        Cloud::bootManagedQueues($this->app);
        $eventsFake = $this->fakeEvents();
        [$queue, $client] = $this->mockedQueue();
        $client->shouldReceive('sendMessage')->times(1)->andReturn(new Result());

        $queue->push(new FakeJob, queue: 'orders.fifo');

        // The suffix is injected before ".fifo", so it must be stripped without
        // leaking into the normalized name.
        $this->assertSame('orders.fifo', $eventsFake->emitted[0]['queue']);
    }

    /**
     * @return array{Queue, MockInterface<SqsClient>}
     */
    private function mockedQueue()
    {
        $client = $this->mock(SqsClient::class);
        $client->shouldReceive('getHandlerList')->andReturn(new HandlerList());

        $this->app->instance(QueueConnector::class, new QueueConnector(new class($client) implements ConnectorInterface
        {
            public function __construct(private $client)
            {
                //
            }

            public function connect($config)
            {
                return new SqsQueue(
                    $this->client,
                    $config['queue'],
                    $config['prefix'] ?? '',
                    $config['suffix'] ?? '',
                    $config['after_commit'] ?? null,
                    $config['overflow'] ?? [],
                );
            }
        }, $this->app));

        $this->app['queue']->addConnector('cloud', $this->app->factory(QueueConnector::class));

        return [$this->app['queue']->connection('cloud'), $client];
    }

    private function fakeEvents()
    {
        return $this->app->instance(Events::class, new class('test-socket') extends Events
        {
            public array $emitted = [];

            public function emitMany(array $payloads): void
            {
                $this->emitted = [
                    ...$this->emitted,
                    ...$payloads,
                ];
            }
        });
    }

    /**
     * Build a Cloud queue whose agent runtime socket is faked via Http::fake().
     *
     * The returned agent exposes pushJob() to script the next GET /next
     * responses; once drained the agent answers 204. POST /result requests are
     * recorded by the HTTP fake and can be asserted with Http::assertSent().
     *
     * @return array{Queue, object{jobs: array}}
     */
    private function fakeQueue($sqs = null)
    {
        // Enable the agent so pop() long-polls the faked runtime socket.
        $this->app['config']->set('queue.connections.cloud.agent', [
            'enabled' => true,
            'socket' => '/tmp/cloud-agent.sock',
        ]);

        // A real client suffices while the SQS seams stay no-ops; callers asserting
        // direct SQS calls pass a mock instead.
        $sqs ??= new SqsClient(['region' => 'us-east-2', 'version' => 'latest', 'credentials' => false]);

        $fakeQueue = new class($this->app, [], null, $sqs) extends QueueFake
        {
            public function __construct($app, $jobs, $failer, private $sqs)
            {
                parent::__construct($app);
            }

            public function getQueue($queue)
            {
                $queue ??= 'default';

                return config('queue.connections.cloud.connection.prefix').'/'.$queue.config('queue.connections.cloud.connection.suffix');
            }

            public function getContainer()
            {
                return $this->app;
            }

            public function getSqs()
            {
                return $this->sqs;
            }

            public function getConnectionName()
            {
                return 'cloud';
            }

            public function setConfig(array $config)
            {
                return $this;
            }

            public function setContainer($container)
            {
                return $this;
            }
        };

        $this->app->instance(QueueConnector::class, new QueueConnector(new class($fakeQueue) implements ConnectorInterface
        {
            public function __construct(private $fakeQueue)
            {
                //
            }

            public function connect($config)
            {
                return $this->fakeQueue;
            }
        }, $this->app));

        $this->app['queue']->addConnector('cloud', $this->app->factory(QueueConnector::class));

        $agent = $this->fakeAgent();

        return [$this->app['queue']->connection('cloud'), $agent];
    }

    /**
     * Fake the cloud-agent runtime socket with Http::fake(): GET /next serves
     * scripted jobs (204 once drained) and POST /result is accepted (and
     * recorded for assertions). The returned object scripts jobs via pushJob().
     */
    private function fakeAgent()
    {
        $agent = new class
        {
            public array $jobs = [];

            public int $resultStatus = 200;

            public bool $resultUnreachable = false;

            public $nextResponse = null;

            public function pushJob(array $job = []): array
            {
                $job = array_merge([
                    'messageId' => (string) Str::uuid(),
                    'receiptHandle' => 'receipt-handle',
                    // The agent always reports the SQS queue URL the message came from.
                    'queueUrl' => 'https://sqs.us-east-1.amazonaws.com/123456789012/default',
                    'body' => json_encode(['job' => MyJob::class, 'data' => []]),
                    // SQS always returns ApproximateReceiveCount, so mirror it.
                    'attributes' => ['ApproximateReceiveCount' => 1],
                ], $job);

                $this->jobs[] = $job;

                return $job;
            }
        };

        Http::fake(function ($request) use ($agent) {
            if (str_ends_with($request->url(), '/next')) {
                if ($agent->nextResponse !== null) {
                    return $agent->nextResponse;
                }

                $job = array_shift($agent->jobs);

                return $job === null
                    ? Http::response('', 204)
                    : Http::response($job, 200);
            }

            if (str_ends_with($request->url(), '/result')) {
                if ($agent->resultUnreachable) {
                    throw new ConnectionException('Connection refused');
                }

                return Http::response('', $agent->resultStatus);
            }

            return Http::response('', 404);
        });

        return $agent;
    }

    private function fakeFailer()
    {
        return new FileFailedJobProvider(tempnam(sys_get_temp_dir(), 'cloud_failed_job_test_'));
    }

    /**
     * Assert the exact sequence of POST /result bodies sent to the agent.
     */
    private function assertAgentResults(array $expected): void
    {
        $results = Http::recorded(fn ($request) => str_ends_with($request->url(), '/result'))
            ->map(fn ($record) => $record[0]->data())
            ->values()
            ->all();

        $this->assertSame($expected, $results);
    }
}

class MyJob
{
    public function fire()
    {
        //
    }
}
