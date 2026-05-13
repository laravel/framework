<?php

namespace Tests\Tests\Foundation;

use Aws\Result;
use Aws\Sqs\SqsClient;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Foundation\Cloud;
use Illuminate\Foundation\Cloud\Events;
use Illuminate\Foundation\Cloud\FailedJobProvider;
use Illuminate\Foundation\Cloud\Queue;
use Illuminate\Foundation\Cloud\QueueConnector;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\Connectors\ConnectorInterface;
use Illuminate\Queue\Connectors\SqsConnector;
use Illuminate\Queue\Failed\FileFailedJobProvider;
use Illuminate\Queue\Jobs\FakeJob;
use Illuminate\Queue\SqsQueue;
use Illuminate\Queue\Worker;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Testing\Fakes\QueueFake;
use Mockery\MockInterface;
use Orchestra\Testbench\Attributes\WithConfig;
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
        $_SERVER['LARAVEL_CLOUD'] = $_SERVER['LARAVEL_CLOUD_MANAGED_QUEUES'] = '1';

        parent::setUp();

        $this->app['config']->set([
            'queue.connections.sqs.prefix' => 'https://sqs.us-east-2.amazonaws.com/1234567',
            'queue.connections.sqs.suffix' => '-env-8280cf2c-2081-47e8-a1f1-9cdfcba8618f',
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($_SERVER['LARAVEL_CLOUD'], $_SERVER['LARAVEL_CLOUD_MANAGED_QUEUES'], $_SERVER['LARAVEL_CLOUD_REGION']);
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

            $this->app['queue']->connection('sqs');
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

            $this->app['queue']->connection('sqs');
            $this->assertFalse(Worker::$pausable);
        } finally {
            $_SERVER['argv'] = $argv;
        }
    }

    #[WithConfig('queue.connections.sqs', ['driver' => 'sqs', 'region' => 'us-east-1', 'queue' => 'default'])]
    public function testItConfiguresManagedQueueCredentials()
    {
        Cloud::configureManagedQueues($this->app);

        $this->assertEquals('ecs', $this->app['config']->get('queue.connections.sqs.credentials'));
    }

    #[WithConfig('queue.connections.sqs', ['driver' => 'sqs', 'region' => 'us-east-1', 'queue' => 'default'])]
    public function testItDoesNotConfigureManagedQueuesWhenNotEnabled()
    {
        unset($_SERVER['LARAVEL_CLOUD_MANAGED_QUEUES']);
        Cloud::configureManagedQueues($this->app);

        $this->assertNull($this->app['config']->get('queue.connections.sqs.credentials'));
    }

    #[WithConfig('queue.connections.sqs', ['driver' => 'sqs', 'region' => 'us-east-1', 'queue' => 'default'])]
    public function testItConfiguresManagedQueueRegion()
    {
        $_SERVER['LARAVEL_CLOUD_MANAGED_QUEUES'] = '1';
        $_SERVER['LARAVEL_CLOUD_REGION'] = 'us-west-2';

        try {
            Cloud::configureManagedQueues($this->app);

            $this->assertEquals('us-west-2', $this->app['config']->get('queue.connections.sqs.region'));
        } finally {
            unset($_SERVER['LARAVEL_CLOUD_MANAGED_QUEUES'], $_SERVER['LARAVEL_CLOUD_REGION']);
        }
    }

    public function testItSetSqsCredentialsToEcs()
    {
        $this->assertSame(null, Config::get('queue.connections.sqs.credentials'));

        Cloud::configureManagedQueues($this->app);

        $this->assertSame('ecs', Config::get('queue.connections.sqs.credentials'));
    }

    public function testItSetsTheSqsRegion()
    {
        $this->assertSame('us-east-1', Config::get('queue.connections.sqs.region'));

        Cloud::configureManagedQueues($this->app);
        $this->assertSame('us-east-1', Config::get('queue.connections.sqs.region'));

        $_SERVER['LARAVEL_CLOUD_REGION'] = 'eu-central-1';
        Cloud::configureManagedQueues($this->app);

        $this->assertSame('eu-central-1', Config::get('queue.connections.sqs.region'));
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

        $this->assertInstanceOf(Queue::class, $this->app['queue']->connection('sqs'));
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

    public function testItDoesNotBindCloudQueueWhenManagedQueuesIsInactive()
    {
        unset($_SERVER['LARAVEL_CLOUD_MANAGED_QUEUES']);

        Cloud::bootManagedQueues($this->app);

        $this->assertInstanceOf(SqsQueue::class, $this->app['queue']->connection('sqs'));
    }

    public function testItDoesNotEmitEventsWhilePoppingWhenNoJobsAreProcessingAndNoJobsAreAvailableToPop()
    {
        $eventsFake = $this->fakeEvents();
        $queueFake = $this->fakeQueue();
        $queue = new Queue($queueFake, $eventsFake, []);

        $queue->pop();

        $this->assertSame([], $eventsFake->emitted);
    }

    public function testItEmitsStartedEventWhenJobIsSuccessfullyPopped()
    {
        $this->travelTo('2000-01-02 03:04:05.060708');
        $eventsFake = $this->fakeEvents();
        $queueFake = $this->fakeQueue();
        $queue = new Queue($queueFake, $eventsFake, $this->app['config']->get('queue.connections.sqs'));

        $queueFake->jobsToPop[] = new FakeJob;
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
        $queueFake = $this->fakeQueue();
        $queue = new Queue($queueFake, $eventsFake, $this->app['config']->get('queue.connections.sqs'));

        $queueFake->jobsToPop[] = new FakeJob;
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
        $queueFake = $this->fakeQueue();
        $queue = new Queue($queueFake, $eventsFake, $this->app['config']->get('queue.connections.sqs'));

        $queueFake->jobsToPop[] = new FakeJob;
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
        $queueFake = $this->fakeQueue();
        $queue = new Queue($queueFake, $eventsFake, $this->app['config']->get('queue.connections.sqs'));

        $queueFake->jobsToPop = [new FakeJob, new FakeJob];
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
        $queueFake = $this->fakeQueue();
        $queue = new Queue($queueFake, $eventsFake, $this->app['config']->get('queue.connections.sqs'));
        $failerFake = $this->fakeFailer();
        $failedJobProvider = new FailedJobProvider($failerFake, $eventsFake, $this->app['encrypter']);
        $failedJobProvider->setQueue($queue);
        $this->app[FailedJobProvider::class] = $failedJobProvider;

        $queueFake->jobsToPop[] = $jobFake = new FakeJob;
        $queue->pop();
        $jobFake->fail();
        Str::createUuidsUsingSequence([Uuid::fromString('00dc709e-90c4-70c2-87c8-9b7127d20e8f')]);
        $failedJobProvider->log('sqs', 'default', ['payload' => 'here'], new RuntimeException('Whoops!'));
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
        $queueFake = $this->fakeQueue();
        $queue = new Queue($queueFake, $eventsFake, $this->app['config']->get('queue.connections.sqs'));

        $queueFake->jobsToPop[] = $jobFake = new FakeJob;
        $queue->pop();
        $jobFake->release();
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

    public function testItRespectsDispatchAfterTransaction()
    {
        $this->travelTo('2000-01-02 03:04:05.060708');
        Cloud::configureManagedQueues($this->app);
        Cloud::bootManagedQueues($this->app);
        $eventsFake = $this->fakeEvents();
        $this->app['config']->set('queue.connections.sqs.after_commit', true);
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
        $queueFake = $this->fakeQueue();
        $queue = new Queue($queueFake, $eventsFake, $this->app['config']->get('queue.connections.sqs'));

        $queueFake->jobsToPop = [new FakeJob, new FakeJob];
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
        $queueFake = $this->fakeQueue();
        $queue = new Queue($queueFake, $eventsFake, $this->app['config']->get('queue.connections.sqs'));

        $queueFake->jobsToPop[] = new FakeJob;
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

        $payload = ['id' => 'test-job-id', 'connection' => 'sqs', 'queue' => 'default', 'payload' => '{"job":"App\\\\Jobs\\\\TestJob"}'];
        $encrypted = Crypt::encryptString(json_encode($payload));

        Http::fake([
            'https://cloud.laravel.com/*' => Http::response($encrypted),
        ]);

        $result = $provider->find('https://cloud.laravel.com/api/jobs/test-job-id?signature=abc');

        $this->assertIsObject($result);
        $this->assertSame('test-job-id', $result->id);
        $this->assertSame('sqs', $result->connection);
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

        $payload = ['id' => 'forget-test-id', 'connection' => 'sqs', 'queue' => 'default', 'payload' => '{}'];
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

    public function testItHandlesMissingPrefixAndSuffixConfig()
    {
        Cloud::configureManagedQueues($this->app);
        Cloud::bootManagedQueues($this->app);
        $eventsFake = $this->fakeEvents();
        $this->app['config']->set('queue.connections.sqs', Arr::except($this->app['config']->get('queue.connections.sqs'), ['prefix', 'suffix']));
        [$queue, $client] = $this->mockedQueue();
        $client->shouldReceive('sendMessage')->times(1)->andReturn(new Result());

        unset($_SERVER['SQS_PREFIX'], $_SERVER['SQS_SUFFIX']);

        $queue->push(new FakeJob, queue: 'https://sqs.us-east-2.amazonaws.com/1234567/my-queue-env-8280cf2c-2081-47e8-a1f1-9cdfcba8618f');

        $this->assertSame('https://sqs.us-east-2.amazonaws.com/1234567/my-queue-env-8280cf2c-2081-47e8-a1f1-9cdfcba8618f', $eventsFake->emitted[0]['queue']);
    }

    /**
     * @return array{Queue, MockInterface<SqsClient>}
     */
    private function mockedQueue()
    {
        $client = $this->mock(SqsClient::class);

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

        return [$this->app['queue']->connection('sqs'), $client];
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

    private function fakeQueue()
    {
        return new class($this->app, [], null) extends QueueFake
        {
            public array $jobsToPop = [];

            public function pop($queue = null)
            {
                return array_shift($this->jobsToPop);
            }

            public function getQueue($queue)
            {
                $queue ??= 'default';

                return config('queue.connections.sqs.prefix').'/'.$queue.config('queue.connections.sqs.suffix');
            }
        };
    }

    private function fakeFailer()
    {
        return new FileFailedJobProvider(tempnam(sys_get_temp_dir(), 'cloud_failed_job_test_'));
    }
}

class MyJob
{
    public function fire()
    {
        //
    }
}
