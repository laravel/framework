<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Bus\Batchable;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\BackoffJitter;
use Illuminate\Queue\Attributes\Delay;
use Illuminate\Queue\Attributes\FailOnTimeout;
use Illuminate\Queue\Attributes\MaxExceptions;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\DatabaseQueue;
use Illuminate\Queue\Jobs\InspectedJob;
use Illuminate\Queue\Queue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

class QueueDatabaseQueueUnitTest extends TestCase
{
    #[DataProvider('pushJobsDataProvider')]
    public function testPushProperlyPushesJobOntoDatabase($uuid, $job, $displayNameStartsWith, $jobStartsWith)
    {
        Str::createUuidsUsing(function () use ($uuid) {
            return $uuid;
        });

        $database = Mockery::mock(Connection::class);
        $queue = $this->getMockBuilder(DatabaseQueue::class)->onlyMethods(['currentTime'])->setConstructorArgs([$database, 'table', 'default'])->getMock();
        $queue->method('currentTime')->willReturn('time');
        $container = Mockery::spy(Container::class);
        $queue->setContainer($container);
        $query = Mockery::mock(QueryBuilder::class);
        $database->expects('table')->with('table')->andReturn($query);
        $query->expects('insertGetId')->andReturnUsing(function ($array) use ($uuid, $displayNameStartsWith, $jobStartsWith) {
            $payload = json_decode($array['payload'], true);
            $this->assertSame($uuid, $payload['uuid']);
            $this->assertStringContainsString($displayNameStartsWith, $payload['displayName']);
            $this->assertStringContainsString($jobStartsWith, $payload['job']);

            $this->assertSame('default', $array['queue']);
            $this->assertEquals(0, $array['attempts']);
            $this->assertNull($array['reserved_at']);
            $this->assertIsInt($array['available_at']);
        });

        $queue->push($job, ['data']);

        $container->shouldHaveReceived('bound')->with('events')->twice();

        Str::createUuidsNormally();
    }

    public static function pushJobsDataProvider()
    {
        $uuid = Str::uuid()->toString();

        return [
            [$uuid, new MyTestJob, 'MyTestJob', 'CallQueuedHandler'],
            [$uuid, fn () => 0, 'Closure', 'CallQueuedHandler'],
            [$uuid, 'foo', 'foo', 'foo'],
        ];
    }

    public function testDelayedPushProperlyPushesJobOntoDatabase()
    {
        $uuid = Str::uuid();

        Str::createUuidsUsing(function () use ($uuid) {
            return $uuid;
        });

        $time = Carbon::now();
        Carbon::setTestNow($time);

        $database = Mockery::mock(Connection::class);
        $queue = $this->getMockBuilder(DatabaseQueue::class)
            ->onlyMethods(['currentTime'])
            ->setConstructorArgs([$database, 'table', 'default'])
            ->getMock();
        $queue->method('currentTime')->willReturn('time');
        $container = Mockery::spy(Container::class);
        $queue->setContainer($container);
        $query = Mockery::mock(QueryBuilder::class);
        $database->expects('table')->with('table')->andReturn($query);
        $query->expects('insertGetId')->andReturnUsing(function ($array) use ($uuid, $time) {
            $this->assertSame('default', $array['queue']);
            $this->assertSame(json_encode(['uuid' => $uuid, 'displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'maxExceptions' => null, 'failOnTimeout' => false, 'backoff' => null, 'backoffJitter' => null, 'timeout' => null, 'data' => ['data'], 'createdAt' => $time->getTimestamp(), 'delay' => 10]), $array['payload']);
            $this->assertEquals(0, $array['attempts']);
            $this->assertNull($array['reserved_at']);
            $this->assertIsInt($array['available_at']);
        });

        $queue->later(10, 'foo', ['data']);

        $container->shouldHaveReceived('bound')->with('events')->twice();

        Str::createUuidsNormally();
    }

    public function testPushIncludesBatchIdInPayloadForBatchableJob()
    {
        $uuid = Str::uuid()->toString();

        Str::createUuidsUsing(function () use ($uuid) {
            return $uuid;
        });

        $job = (new MyBatchableJob)->withBatchId('test-batch-id');

        $database = Mockery::mock(Connection::class);
        $queue = $this->getMockBuilder(DatabaseQueue::class)->onlyMethods(['currentTime'])->setConstructorArgs([$database, 'table', 'default'])->getMock();
        $queue->method('currentTime')->willReturn('time');
        $container = Mockery::spy(Container::class);
        $queue->setContainer($container);
        $query = Mockery::mock(QueryBuilder::class);
        $database->expects('table')->with('table')->andReturn($query);
        $query->expects('insertGetId')->andReturnUsing(function ($array) {
            $payload = json_decode($array['payload'], true);
            $this->assertSame('test-batch-id', $payload['data']['batchId']);
        });

        $queue->push($job, ['data']);

        $container->shouldHaveReceived('bound')->with('events')->twice();

        Str::createUuidsNormally();
    }

    public function testPushUsesPropertiesDeclaredOnChildClassOverInheritedAttributes()
    {
        $database = Mockery::mock(Connection::class);
        $queue = new DatabaseQueue($database, 'table', 'default');
        $container = Mockery::spy(Container::class);
        $queue->setContainer($container);
        $query = Mockery::mock(QueryBuilder::class);
        $database->expects('table')->with('table')->andReturn($query);
        $query->expects('insertGetId')->andReturnUsing(function ($array) {
            $payload = json_decode($array['payload'], true);

            $this->assertSame(1700, $payload['timeout']);
            $this->assertSame(7, $payload['maxTries']);
            $this->assertSame('13', $payload['backoff']);
            $this->assertSame(11, $payload['maxExceptions']);
            $this->assertFalse($payload['failOnTimeout']);
        });

        $queue->push(new ChildJobWithPropertiesOverridingParentAttributes, ['data']);

        $container->shouldHaveReceived('bound')->with('events')->twice();
    }

    public function testPushResolvesBackoffJitterFromAttributeAndProperty()
    {
        $payloads = [];

        foreach ([new JobWithBackoffJitterAttribute, new JobWithBackoffJitterProperty, new JobWithoutBackoffJitter] as $job) {
            $database = Mockery::mock(Connection::class);
            $queue = new DatabaseQueue($database, 'table', 'default');
            $container = Mockery::spy(Container::class);
            $queue->setContainer($container);
            $query = Mockery::mock(QueryBuilder::class);
            $database->expects('table')->with('table')->andReturn($query);
            $query->expects('insertGetId')->andReturnUsing(function ($array) use (&$payloads) {
                $payloads[] = json_decode($array['payload'], true);
            });

            $queue->push($job, ['data']);
        }

        $this->assertSame(BackoffJitter::DEFAULT_RATIO, $payloads[0]['backoffJitter']);
        $this->assertSame(0.5, $payloads[1]['backoffJitter']);
        $this->assertNull($payloads[2]['backoffJitter']);
    }

    public function testPushStillUsesAttributesDeclaredOnSameClassOverDefaultProperties()
    {
        $database = Mockery::mock(Connection::class);
        $queue = new DatabaseQueue($database, 'table', 'default');
        $container = Mockery::spy(Container::class);
        $queue->setContainer($container);
        $query = Mockery::mock(QueryBuilder::class);
        $database->expects('table')->with('table')->andReturn($query);
        $query->expects('insertGetId')->andReturnUsing(function ($array) {
            $payload = json_decode($array['payload'], true);

            $this->assertSame(40, $payload['timeout']);
            $this->assertSame(2, $payload['maxTries']);
            $this->assertSame('9', $payload['backoff']);
            $this->assertSame(3, $payload['maxExceptions']);
            $this->assertTrue($payload['failOnTimeout']);
        });

        $queue->push(new JobWithAttributesAndDefaultProperties, ['data']);

        $container->shouldHaveReceived('bound')->with('events')->twice();
    }

    public function testFailureToCreatePayloadFromObject()
    {
        $this->expectException('InvalidArgumentException');

        $job = new stdClass;
        $job->invalid = "\xc3\x28";

        $queue = Mockery::mock(Queue::class)->makePartial();
        $class = new ReflectionClass(Queue::class);

        $createPayload = $class->getMethod('createPayload');
        $createPayload->invokeArgs($queue, [
            $job,
            'queue-name',
        ]);
    }

    public function testFailureToCreatePayloadFromArray()
    {
        $this->expectException('InvalidArgumentException');

        $queue = Mockery::mock(Queue::class)->makePartial();
        $class = new ReflectionClass(Queue::class);

        $createPayload = $class->getMethod('createPayload');
        $createPayload->invokeArgs($queue, [
            ["\xc3\x28"],
            'queue-name',
        ]);
    }

    public function testBulkBatchPushesOntoDatabase()
    {
        $uuid = Str::uuid();

        Str::createUuidsUsing(function () use ($uuid) {
            return $uuid;
        });

        $time = Carbon::now();
        Carbon::setTestNow($time);

        $database = Mockery::mock(Connection::class);
        $queue = $this->getMockBuilder(DatabaseQueue::class)->onlyMethods(['currentTime', 'availableAt'])->setConstructorArgs([$database, 'table', 'default'])->getMock();
        $queue->method('currentTime')->willReturn('created');
        $queue->method('availableAt')->willReturn('available');
        $query = Mockery::mock(QueryBuilder::class);
        $database->expects('table')->with('table')->andReturn($query);
        $query->expects('insert')->andReturnUsing(function ($records) use ($uuid, $time) {
            $this->assertEquals([[
                'queue' => 'queue',
                'payload' => json_encode(['uuid' => $uuid, 'displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'maxExceptions' => null, 'failOnTimeout' => false, 'backoff' => null, 'backoffJitter' => null, 'timeout' => null, 'data' => ['data'], 'createdAt' => $time->getTimestamp(), 'delay' => null]),
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => 'available',
                'created_at' => 'created',
            ], [
                'queue' => 'queue',
                'payload' => json_encode(['uuid' => $uuid, 'displayName' => 'bar', 'job' => 'bar', 'maxTries' => null, 'maxExceptions' => null, 'failOnTimeout' => false, 'backoff' => null, 'backoffJitter' => null, 'timeout' => null, 'data' => ['data'], 'createdAt' => $time->getTimestamp(), 'delay' => null]),
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => 'available',
                'created_at' => 'created',
            ]], $records);
        });

        $queue->bulk(['foo', 'bar'], ['data'], 'queue');

        Str::createUuidsNormally();
    }

    public function testDelayAttributeIsRespectedWhenBulkPushing()
    {
        $database = Mockery::mock(Connection::class);
        $queue = $this->getMockBuilder(DatabaseQueue::class)->onlyMethods(['currentTime', 'availableAt'])->setConstructorArgs([$database, 'table', 'default'])->getMock();
        $queue->method('currentTime')->willReturn('created');
        $queue->method('availableAt')->willReturnCallback(function ($delay = 0) {
            return 'available:'.$delay;
        });
        $query = Mockery::mock(QueryBuilder::class);
        $database->expects('table')->with('table')->andReturn($query);
        $query->expects('insert')->andReturnUsing(function ($records) {
            $this->assertSame('available:15', $records[0]['available_at']);
        });

        $queue->bulk([new JobWithDelayAttribute], ['data'], 'queue');
    }

    public function testBulkDefersAfterCommitJobsUntilTheTransactionCommits()
    {
        $transactions = Mockery::mock(\Illuminate\Database\DatabaseTransactionsManager::class);

        $committed = null;

        $transactions->expects('addCallback')->andReturnUsing(function ($callback) use (&$committed) {
            $committed = $callback;
        });

        $container = new Container;
        $container->instance('db.transactions', $transactions);

        $database = Mockery::mock(Connection::class);
        $queue = new DatabaseQueue($database, 'table', 'default');
        $queue->setContainer($container);

        $inserted = false;

        $query = Mockery::mock(QueryBuilder::class);
        $database->expects('table')->with('table')->andReturn($query);
        $query->expects('insert')->andReturnUsing(function () use (&$inserted) {
            $inserted = true;
        });

        $queue->bulk([new AfterCommitJob]);

        $this->assertNotNull($committed);
        $this->assertFalse($inserted);

        $committed();

        $this->assertTrue($inserted);
    }

    public function testBuildDatabaseRecordWithPayloadAtTheEnd()
    {
        $queue = Mockery::mock(DatabaseQueue::class);
        $record = $queue->buildDatabaseRecord('queue', 'any_payload', 0);
        $this->assertArrayHasKey('payload', $record);
        $this->assertArrayHasKey('payload', array_slice($record, -1, 1, true));
    }

    public function testPendingJobs()
    {
        $database = Mockery::mock(Connection::class);
        $queue = new DatabaseQueue($database, 'table', 'default');
        $queue->setContainer(Mockery::spy(Container::class));

        $payload = json_encode(['uuid' => 'test-uuid', 'displayName' => 'MyTestJob', 'job' => 'foo', 'data' => [], 'createdAt' => 1000000]);

        $query = Mockery::mock(QueryBuilder::class);
        $database->expects('table')->with('table')->andReturn($query);
        $query->expects('where')->with('queue', 'default')->andReturnSelf();
        $query->expects('whereNull')->with('reserved_at')->andReturnSelf();
        $query->expects('where')->with('available_at', '<=', Mockery::any())->andReturnSelf();
        $query->expects('get')->andReturn(collect([(object) ['id' => 1, 'queue' => 'default', 'payload' => $payload, 'attempts' => 0, 'reserved_at' => null]]));

        $jobs = $queue->pendingJobs();

        $this->assertCount(1, $jobs);
        $this->assertInstanceOf(InspectedJob::class, $jobs->first());
        $this->assertSame('MyTestJob', $jobs->first()->name);
        $this->assertSame('test-uuid', $jobs->first()->uuid);
        $this->assertSame(0, $jobs->first()->attempts);
        $this->assertSame('default', $jobs->first()->queue);
        $this->assertInstanceOf(Carbon::class, $jobs->first()->createdAt);
        $this->assertSame(1000000, $jobs->first()->createdAt->getTimestamp());
    }

    public function testDelayedJobs()
    {
        $database = Mockery::mock(Connection::class);
        $queue = new DatabaseQueue($database, 'table', 'default');
        $queue->setContainer(Mockery::spy(Container::class));

        $payload = json_encode(['uuid' => 'test-uuid', 'displayName' => 'MyDelayedJob', 'job' => 'foo', 'data' => [], 'createdAt' => 1000000]);

        $query = Mockery::mock(QueryBuilder::class);
        $database->expects('table')->with('table')->andReturn($query);
        $query->expects('where')->with('queue', 'default')->andReturnSelf();
        $query->expects('whereNull')->with('reserved_at')->andReturnSelf();
        $query->expects('where')->with('available_at', '>', Mockery::any())->andReturnSelf();
        $query->expects('get')->andReturn(collect([(object) ['id' => 2, 'queue' => 'default', 'payload' => $payload, 'attempts' => 0, 'reserved_at' => null]]));

        $jobs = $queue->delayedJobs();

        $this->assertCount(1, $jobs);
        $this->assertInstanceOf(InspectedJob::class, $jobs->first());
        $this->assertSame('MyDelayedJob', $jobs->first()->name);
        $this->assertSame('test-uuid', $jobs->first()->uuid);
        $this->assertSame(0, $jobs->first()->attempts);
        $this->assertSame('default', $jobs->first()->queue);
        $this->assertInstanceOf(Carbon::class, $jobs->first()->createdAt);
        $this->assertSame(1000000, $jobs->first()->createdAt->getTimestamp());
    }

    public function testReservedJobs()
    {
        $database = Mockery::mock(Connection::class);
        $queue = new DatabaseQueue($database, 'table', 'default');
        $queue->setContainer(Mockery::spy(Container::class));

        $payload = json_encode(['uuid' => 'test-uuid', 'displayName' => 'MyTestJob', 'job' => 'foo', 'data' => [], 'createdAt' => 1000000]);

        $query = Mockery::mock(QueryBuilder::class);
        $database->expects('table')->with('table')->andReturn($query);
        $query->expects('where')->with('queue', 'default')->andReturnSelf();
        $query->expects('whereNotNull')->with('reserved_at')->andReturnSelf();
        $query->expects('get')->andReturn(collect([(object) ['id' => 1, 'queue' => 'default', 'payload' => $payload, 'attempts' => 1, 'reserved_at' => Carbon::now()->getTimestamp()]]));

        $jobs = $queue->reservedJobs();

        $this->assertCount(1, $jobs);
        $this->assertInstanceOf(InspectedJob::class, $jobs->first());
        $this->assertSame('MyTestJob', $jobs->first()->name);
        $this->assertSame('test-uuid', $jobs->first()->uuid);
        $this->assertSame(1, $jobs->first()->attempts);
        $this->assertSame('default', $jobs->first()->queue);
        $this->assertInstanceOf(Carbon::class, $jobs->first()->createdAt);
        $this->assertSame(1000000, $jobs->first()->createdAt->getTimestamp());
    }

    public function testAllPendingJobs()
    {
        $database = Mockery::mock(Connection::class);
        $queue = new DatabaseQueue($database, 'table', 'default');
        $queue->setContainer(Mockery::spy(Container::class));

        $payload1 = json_encode(['uuid' => 'uuid-1', 'displayName' => 'JobA', 'job' => 'foo', 'data' => [], 'createdAt' => 1000000]);
        $payload2 = json_encode(['uuid' => 'uuid-2', 'displayName' => 'JobB', 'job' => 'foo', 'data' => [], 'createdAt' => 1000001]);

        $query = Mockery::mock(QueryBuilder::class);
        $database->expects('table')->with('table')->andReturn($query);
        $query->expects('whereNull')->with('reserved_at')->andReturnSelf();
        $query->expects('where')->with('available_at', '<=', Mockery::any())->andReturnSelf();
        $query->expects('get')->andReturn(collect([
            (object) ['id' => 1, 'queue' => 'default', 'payload' => $payload1, 'attempts' => 0, 'reserved_at' => null],
            (object) ['id' => 2, 'queue' => 'emails', 'payload' => $payload2, 'attempts' => 0, 'reserved_at' => null],
        ]));

        $jobs = $queue->allPendingJobs();

        $this->assertCount(2, $jobs);
        $this->assertInstanceOf(InspectedJob::class, $jobs->first());
        $this->assertSame('JobA', $jobs->first()->name);
        $this->assertSame('uuid-1', $jobs->first()->uuid);
        $this->assertSame(0, $jobs->first()->attempts);
        $this->assertSame('default', $jobs->first()->queue);
        $this->assertInstanceOf(Carbon::class, $jobs->first()->createdAt);
        $this->assertSame(1000000, $jobs->first()->createdAt->getTimestamp());
        $this->assertSame('JobB', $jobs->last()->name);
        $this->assertSame('uuid-2', $jobs->last()->uuid);
        $this->assertSame('emails', $jobs->last()->queue);
    }

    public function testAllDelayedJobs()
    {
        $database = Mockery::mock(Connection::class);
        $queue = new DatabaseQueue($database, 'table', 'default');
        $queue->setContainer(Mockery::spy(Container::class));

        $payload1 = json_encode(['uuid' => 'uuid-1', 'displayName' => 'JobA', 'job' => 'foo', 'data' => [], 'createdAt' => 1000000]);
        $payload2 = json_encode(['uuid' => 'uuid-2', 'displayName' => 'JobB', 'job' => 'foo', 'data' => [], 'createdAt' => 1000001]);

        $query = Mockery::mock(QueryBuilder::class);
        $database->expects('table')->with('table')->andReturn($query);
        $query->expects('whereNull')->with('reserved_at')->andReturnSelf();
        $query->expects('where')->with('available_at', '>', Mockery::any())->andReturnSelf();
        $query->expects('get')->andReturn(collect([
            (object) ['id' => 1, 'queue' => 'default', 'payload' => $payload1, 'attempts' => 0, 'reserved_at' => null],
            (object) ['id' => 2, 'queue' => 'emails', 'payload' => $payload2, 'attempts' => 0, 'reserved_at' => null],
        ]));

        $jobs = $queue->allDelayedJobs();

        $this->assertCount(2, $jobs);
        $this->assertInstanceOf(InspectedJob::class, $jobs->first());
        $this->assertSame('JobA', $jobs->first()->name);
        $this->assertSame('uuid-1', $jobs->first()->uuid);
        $this->assertSame(0, $jobs->first()->attempts);
        $this->assertSame('default', $jobs->first()->queue);
        $this->assertInstanceOf(Carbon::class, $jobs->first()->createdAt);
        $this->assertSame(1000000, $jobs->first()->createdAt->getTimestamp());
        $this->assertSame('JobB', $jobs->last()->name);
        $this->assertSame('uuid-2', $jobs->last()->uuid);
        $this->assertSame('emails', $jobs->last()->queue);
    }

    public function testAllReservedJobs()
    {
        $database = Mockery::mock(Connection::class);
        $queue = new DatabaseQueue($database, 'table', 'default');
        $queue->setContainer(Mockery::spy(Container::class));

        $payload1 = json_encode(['uuid' => 'uuid-1', 'displayName' => 'JobA', 'job' => 'foo', 'data' => [], 'createdAt' => 1000000]);
        $payload2 = json_encode(['uuid' => 'uuid-2', 'displayName' => 'JobB', 'job' => 'foo', 'data' => [], 'createdAt' => 1000001]);

        $query = Mockery::mock(QueryBuilder::class);
        $database->expects('table')->with('table')->andReturn($query);
        $query->expects('whereNotNull')->with('reserved_at')->andReturnSelf();
        $query->expects('get')->andReturn(collect([
            (object) ['id' => 1, 'queue' => 'default', 'payload' => $payload1, 'attempts' => 1, 'reserved_at' => 1000005],
            (object) ['id' => 2, 'queue' => 'emails', 'payload' => $payload2, 'attempts' => 2, 'reserved_at' => 1000006],
        ]));

        $jobs = $queue->allReservedJobs();

        $this->assertCount(2, $jobs);
        $this->assertInstanceOf(InspectedJob::class, $jobs->first());
        $this->assertSame('JobA', $jobs->first()->name);
        $this->assertSame('uuid-1', $jobs->first()->uuid);
        $this->assertSame(1, $jobs->first()->attempts);
        $this->assertSame('default', $jobs->first()->queue);
        $this->assertInstanceOf(Carbon::class, $jobs->first()->createdAt);
        $this->assertSame(1000000, $jobs->first()->createdAt->getTimestamp());
        $this->assertSame('JobB', $jobs->last()->name);
        $this->assertSame('uuid-2', $jobs->last()->uuid);
        $this->assertSame(2, $jobs->last()->attempts);
        $this->assertSame('emails', $jobs->last()->queue);
    }

    public function testTotalPendingSize()
    {
        $database = Mockery::mock(Connection::class);
        $queue = new DatabaseQueue($database, 'table', 'default');
        $queue->setContainer(Mockery::spy(Container::class));

        $query = Mockery::mock(QueryBuilder::class);
        $database->expects('table')->with('table')->andReturn($query);
        $query->expects('whereNull')->with('reserved_at')->andReturnSelf();
        $query->expects('where')->with('available_at', '<=', Mockery::any())->andReturnSelf();
        $query->expects('count')->andReturn(2);

        $this->assertSame(2, $queue->totalPendingSize());
    }

    public function testTotalDelayedSize()
    {
        $database = Mockery::mock(Connection::class);
        $queue = new DatabaseQueue($database, 'table', 'default');
        $queue->setContainer(Mockery::spy(Container::class));

        $query = Mockery::mock(QueryBuilder::class);
        $database->expects('table')->with('table')->andReturn($query);
        $query->expects('whereNull')->with('reserved_at')->andReturnSelf();
        $query->expects('where')->with('available_at', '>', Mockery::any())->andReturnSelf();
        $query->expects('count')->andReturn(3);

        $this->assertSame(3, $queue->totalDelayedSize());
    }

    public function testTotalReservedSize()
    {
        $database = Mockery::mock(Connection::class);
        $queue = new DatabaseQueue($database, 'table', 'default');
        $queue->setContainer(Mockery::spy(Container::class));

        $query = Mockery::mock(QueryBuilder::class);
        $database->expects('table')->with('table')->andReturn($query);
        $query->expects('whereNotNull')->with('reserved_at')->andReturnSelf();
        $query->expects('count')->andReturn(4);

        $this->assertSame(4, $queue->totalReservedSize());
    }

    public function testGetLockForPoppingIsCached()
    {
        $database = Mockery::mock(Connection::class);
        $queue = new DatabaseQueue($database, 'table', 'default');

        $pdo = Mockery::mock(\PDO::class);
        $pdo->expects('getAttribute')->with(\PDO::ATTR_DRIVER_NAME)->andReturn('mysql');
        $pdo->expects('getAttribute')->with(\PDO::ATTR_SERVER_VERSION)->andReturn('8.0.36');

        $database->expects('getPdo')->times(2)->andReturn($pdo);
        $database->expects('getConfig')->with('version')->andReturn(null);

        $method = new \ReflectionMethod($queue, 'getLockForPopping');

        $result1 = $method->invoke($queue);
        $result2 = $method->invoke($queue);

        $this->assertSame('FOR UPDATE SKIP LOCKED', $result1);
        $this->assertSame($result1, $result2);
    }
}

class MyTestJob
{
    public function handle()
    {
        // ...
    }
}

class MyBatchableJob
{
    use Batchable;
}

#[Delay(15)]
class JobWithDelayAttribute
{
}

class AfterCommitJob implements ShouldQueue
{
    public $afterCommit = true;
}

#[Backoff(9)]
#[FailOnTimeout]
#[MaxExceptions(3)]
#[Timeout(40)]
#[Tries(2)]
abstract class ParentJobWithAttributes implements ShouldQueue
{
}

#[BackoffJitter]
class JobWithBackoffJitterAttribute implements ShouldQueue
{
}

class JobWithBackoffJitterProperty implements ShouldQueue
{
    public $backoffJitter = 0.5;
}

class JobWithoutBackoffJitter implements ShouldQueue
{
}

class ChildJobWithPropertiesOverridingParentAttributes extends ParentJobWithAttributes
{
    public $backoff = 13;

    public $failOnTimeout = false;

    public $maxExceptions = 11;

    public $timeout = 1700;

    public $tries = 7;
}

#[Backoff(9)]
#[FailOnTimeout]
#[MaxExceptions(3)]
#[Timeout(40)]
#[Tries(2)]
class JobWithAttributesAndDefaultProperties implements ShouldQueue
{
    public $backoff = 13;

    public $failOnTimeout = false;

    public $maxExceptions = 11;

    public $timeout = 1700;

    public $tries = 7;
}
