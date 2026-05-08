<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Bus\Batchable;
use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Queue\DatabaseQueue;
use Illuminate\Queue\Jobs\InspectedJob;
use Illuminate\Queue\Queue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Mockery as m;
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

        $queue = $this->getMockBuilder(DatabaseQueue::class)->onlyMethods(['currentTime'])->setConstructorArgs([$database = m::mock(Connection::class), 'table', 'default'])->getMock();
        $queue->method('currentTime')->willReturn('time');
        $queue->setContainer($container = m::spy(Container::class));
        $database->shouldReceive('table')->with('table')->andReturn($query = m::mock(stdClass::class));
        $query->shouldReceive('insertGetId')->once()->andReturnUsing(function ($array) use ($uuid, $displayNameStartsWith, $jobStartsWith) {
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

        $queue = $this->getMockBuilder(DatabaseQueue::class)
            ->onlyMethods(['currentTime'])
            ->setConstructorArgs([$database = m::mock(Connection::class), 'table', 'default'])
            ->getMock();
        $queue->method('currentTime')->willReturn('time');
        $queue->setContainer($container = m::spy(Container::class));
        $database->shouldReceive('table')->with('table')->andReturn($query = m::mock(stdClass::class));
        $query->shouldReceive('insertGetId')->once()->andReturnUsing(function ($array) use ($uuid, $time) {
            $this->assertSame('default', $array['queue']);
            $this->assertSame(json_encode(['uuid' => $uuid, 'displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'maxExceptions' => null, 'failOnTimeout' => false, 'backoff' => null, 'timeout' => null, 'data' => ['data'], 'createdAt' => $time->getTimestamp(), 'delay' => 10]), $array['payload']);
            $this->assertEquals(0, $array['attempts']);
            $this->assertNull($array['reserved_at']);
            $this->assertIsInt($array['available_at']);
        });

        $queue->later(10, 'foo', ['data']);

        $container->shouldHaveReceived('bound')->with('events')->twice();

        Carbon::setTestNow();
        Str::createUuidsNormally();
    }

    public function testPushIncludesBatchIdInPayloadForBatchableJob()
    {
        $uuid = Str::uuid()->toString();

        Str::createUuidsUsing(function () use ($uuid) {
            return $uuid;
        });

        $job = (new MyBatchableJob)->withBatchId('test-batch-id');

        $queue = $this->getMockBuilder(DatabaseQueue::class)->onlyMethods(['currentTime'])->setConstructorArgs([$database = m::mock(Connection::class), 'table', 'default'])->getMock();
        $queue->method('currentTime')->willReturn('time');
        $queue->setContainer($container = m::spy(Container::class));
        $database->shouldReceive('table')->with('table')->andReturn($query = m::mock(stdClass::class));
        $query->shouldReceive('insertGetId')->once()->andReturnUsing(function ($array) {
            $payload = json_decode($array['payload'], true);
            $this->assertSame('test-batch-id', $payload['data']['batchId']);
        });

        $queue->push($job, ['data']);

        $container->shouldHaveReceived('bound')->with('events')->twice();

        Str::createUuidsNormally();
    }

    public function testFailureToCreatePayloadFromObject()
    {
        $this->expectException('InvalidArgumentException');

        $job = new stdClass;
        $job->invalid = "\xc3\x28";

        $queue = m::mock(Queue::class)->makePartial();
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

        $queue = m::mock(Queue::class)->makePartial();
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

        $database = m::mock(Connection::class);
        $queue = $this->getMockBuilder(DatabaseQueue::class)->onlyMethods(['currentTime', 'availableAt'])->setConstructorArgs([$database, 'table', 'default'])->getMock();
        $queue->method('currentTime')->willReturn('created');
        $queue->method('availableAt')->willReturn('available');
        $database->shouldReceive('table')->with('table')->andReturn($query = m::mock(stdClass::class));
        $query->shouldReceive('insert')->once()->andReturnUsing(function ($records) use ($uuid, $time) {
            $this->assertEquals([[
                'queue' => 'queue',
                'payload' => json_encode(['uuid' => $uuid, 'displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'maxExceptions' => null, 'failOnTimeout' => false, 'backoff' => null, 'timeout' => null, 'data' => ['data'], 'createdAt' => $time->getTimestamp(), 'delay' => null]),
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => 'available',
                'created_at' => 'created',
            ], [
                'queue' => 'queue',
                'payload' => json_encode(['uuid' => $uuid, 'displayName' => 'bar', 'job' => 'bar', 'maxTries' => null, 'maxExceptions' => null, 'failOnTimeout' => false, 'backoff' => null, 'timeout' => null, 'data' => ['data'], 'createdAt' => $time->getTimestamp(), 'delay' => null]),
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => 'available',
                'created_at' => 'created',
            ]], $records);
        });

        $queue->bulk(['foo', 'bar'], ['data'], 'queue');

        Carbon::setTestNow();
        Str::createUuidsNormally();
    }

    public function testBuildDatabaseRecordWithPayloadAtTheEnd()
    {
        $queue = m::mock(DatabaseQueue::class);
        $record = $queue->buildDatabaseRecord('queue', 'any_payload', 0);
        $this->assertArrayHasKey('payload', $record);
        $this->assertArrayHasKey('payload', array_slice($record, -1, 1, true));
    }

    public function testPendingJobs()
    {
        $queue = new DatabaseQueue($database = m::mock(Connection::class), 'table', 'default');
        $queue->setContainer(m::spy(Container::class));

        $payload = json_encode(['uuid' => 'test-uuid', 'displayName' => 'MyTestJob', 'job' => 'foo', 'data' => [], 'createdAt' => 1000000]);

        $database->shouldReceive('table')->with('table')->andReturn($query = m::mock(stdClass::class));
        $query->shouldReceive('where')->with('queue', 'default')->andReturnSelf();
        $query->shouldReceive('whereNull')->with('reserved_at')->andReturnSelf();
        $query->shouldReceive('where')->with('available_at', '<=', m::any())->andReturnSelf();
        $query->shouldReceive('get')->andReturn(new Collection([(object) ['id' => 1, 'queue' => 'default', 'payload' => $payload, 'attempts' => 0, 'reserved_at' => null]]));

        $jobs = $queue->pendingJobs();

        $this->assertCount(1, $jobs);
        $this->assertInstanceOf(InspectedJob::class, $jobs->first());
        $this->assertSame('MyTestJob', $jobs->first()->name);
        $this->assertSame('test-uuid', $jobs->first()->uuid);
        $this->assertSame(0, $jobs->first()->attempts);
        $this->assertInstanceOf(Carbon::class, $jobs->first()->createdAt);
        $this->assertSame(1000000, $jobs->first()->createdAt->getTimestamp());
    }

    public function testDelayedJobs()
    {
        $queue = new DatabaseQueue($database = m::mock(Connection::class), 'table', 'default');
        $queue->setContainer(m::spy(Container::class));

        $payload = json_encode(['uuid' => 'test-uuid', 'displayName' => 'MyDelayedJob', 'job' => 'foo', 'data' => [], 'createdAt' => 1000000]);

        $database->shouldReceive('table')->with('table')->andReturn($query = m::mock(stdClass::class));
        $query->shouldReceive('where')->with('queue', 'default')->andReturnSelf();
        $query->shouldReceive('whereNull')->with('reserved_at')->andReturnSelf();
        $query->shouldReceive('where')->with('available_at', '>', m::any())->andReturnSelf();
        $query->shouldReceive('get')->andReturn(new Collection([(object) ['id' => 2, 'queue' => 'default', 'payload' => $payload, 'attempts' => 0, 'reserved_at' => null]]));

        $jobs = $queue->delayedJobs();

        $this->assertCount(1, $jobs);
        $this->assertInstanceOf(InspectedJob::class, $jobs->first());
        $this->assertSame('MyDelayedJob', $jobs->first()->name);
        $this->assertSame('test-uuid', $jobs->first()->uuid);
        $this->assertSame(0, $jobs->first()->attempts);
        $this->assertInstanceOf(Carbon::class, $jobs->first()->createdAt);
        $this->assertSame(1000000, $jobs->first()->createdAt->getTimestamp());
    }

    public function testReservedJobs()
    {
        $queue = new DatabaseQueue($database = m::mock(Connection::class), 'table', 'default');
        $queue->setContainer(m::spy(Container::class));

        $payload = json_encode(['uuid' => 'test-uuid', 'displayName' => 'MyTestJob', 'job' => 'foo', 'data' => [], 'createdAt' => 1000000]);

        $database->shouldReceive('table')->with('table')->andReturn($query = m::mock(stdClass::class));
        $query->shouldReceive('where')->with('queue', 'default')->andReturnSelf();
        $query->shouldReceive('whereNotNull')->with('reserved_at')->andReturnSelf();
        $query->shouldReceive('get')->andReturn(new Collection([(object) ['id' => 1, 'queue' => 'default', 'payload' => $payload, 'attempts' => 1, 'reserved_at' => Carbon::now()->getTimestamp()]]));

        $jobs = $queue->reservedJobs();

        $this->assertCount(1, $jobs);
        $this->assertInstanceOf(InspectedJob::class, $jobs->first());
        $this->assertSame('MyTestJob', $jobs->first()->name);
        $this->assertSame('test-uuid', $jobs->first()->uuid);
        $this->assertSame(1, $jobs->first()->attempts);
        $this->assertInstanceOf(Carbon::class, $jobs->first()->createdAt);
        $this->assertSame(1000000, $jobs->first()->createdAt->getTimestamp());
    }

    public function testAllPendingJobs()
    {
        $queue = new DatabaseQueue($database = m::mock(Connection::class), 'table', 'default');
        $queue->setContainer(m::spy(Container::class));

        $payload1 = json_encode(['uuid' => 'uuid-1', 'displayName' => 'JobA', 'job' => 'foo', 'data' => [], 'createdAt' => 1000000]);
        $payload2 = json_encode(['uuid' => 'uuid-2', 'displayName' => 'JobB', 'job' => 'foo', 'data' => [], 'createdAt' => 1000001]);

        $database->shouldReceive('table')->with('table')->andReturn($query = m::mock(stdClass::class));
        $query->shouldReceive('whereNull')->with('reserved_at')->andReturnSelf();
        $query->shouldReceive('where')->with('available_at', '<=', m::any())->andReturnSelf();
        $query->shouldReceive('get')->andReturn(new Collection([
            (object) ['id' => 1, 'queue' => 'default', 'payload' => $payload1, 'attempts' => 0, 'reserved_at' => null],
            (object) ['id' => 2, 'queue' => 'emails', 'payload' => $payload2, 'attempts' => 0, 'reserved_at' => null],
        ]));

        $jobs = $queue->allPendingJobs();

        $this->assertCount(2, $jobs);
        $this->assertInstanceOf(InspectedJob::class, $jobs->first());
        $this->assertSame('JobA', $jobs->first()->name);
        $this->assertSame('uuid-1', $jobs->first()->uuid);
        $this->assertSame(0, $jobs->first()->attempts);
        $this->assertInstanceOf(Carbon::class, $jobs->first()->createdAt);
        $this->assertSame(1000000, $jobs->first()->createdAt->getTimestamp());
        $this->assertSame('JobB', $jobs->last()->name);
        $this->assertSame('uuid-2', $jobs->last()->uuid);
    }

    public function testAllDelayedJobs()
    {
        $queue = new DatabaseQueue($database = m::mock(Connection::class), 'table', 'default');
        $queue->setContainer(m::spy(Container::class));

        $payload1 = json_encode(['uuid' => 'uuid-1', 'displayName' => 'JobA', 'job' => 'foo', 'data' => [], 'createdAt' => 1000000]);
        $payload2 = json_encode(['uuid' => 'uuid-2', 'displayName' => 'JobB', 'job' => 'foo', 'data' => [], 'createdAt' => 1000001]);

        $database->shouldReceive('table')->with('table')->andReturn($query = m::mock(stdClass::class));
        $query->shouldReceive('whereNull')->with('reserved_at')->andReturnSelf();
        $query->shouldReceive('where')->with('available_at', '>', m::any())->andReturnSelf();
        $query->shouldReceive('get')->andReturn(new Collection([
            (object) ['id' => 1, 'queue' => 'default', 'payload' => $payload1, 'attempts' => 0, 'reserved_at' => null],
            (object) ['id' => 2, 'queue' => 'emails', 'payload' => $payload2, 'attempts' => 0, 'reserved_at' => null],
        ]));

        $jobs = $queue->allDelayedJobs();

        $this->assertCount(2, $jobs);
        $this->assertInstanceOf(InspectedJob::class, $jobs->first());
        $this->assertSame('JobA', $jobs->first()->name);
        $this->assertSame('uuid-1', $jobs->first()->uuid);
        $this->assertSame(0, $jobs->first()->attempts);
        $this->assertInstanceOf(Carbon::class, $jobs->first()->createdAt);
        $this->assertSame(1000000, $jobs->first()->createdAt->getTimestamp());
        $this->assertSame('JobB', $jobs->last()->name);
        $this->assertSame('uuid-2', $jobs->last()->uuid);
    }

    public function testAllReservedJobs()
    {
        $queue = new DatabaseQueue($database = m::mock(Connection::class), 'table', 'default');
        $queue->setContainer(m::spy(Container::class));

        $payload1 = json_encode(['uuid' => 'uuid-1', 'displayName' => 'JobA', 'job' => 'foo', 'data' => [], 'createdAt' => 1000000]);
        $payload2 = json_encode(['uuid' => 'uuid-2', 'displayName' => 'JobB', 'job' => 'foo', 'data' => [], 'createdAt' => 1000001]);

        $database->shouldReceive('table')->with('table')->andReturn($query = m::mock(stdClass::class));
        $query->shouldReceive('whereNotNull')->with('reserved_at')->andReturnSelf();
        $query->shouldReceive('get')->andReturn(new Collection([
            (object) ['id' => 1, 'queue' => 'default', 'payload' => $payload1, 'attempts' => 1, 'reserved_at' => 1000005],
            (object) ['id' => 2, 'queue' => 'emails', 'payload' => $payload2, 'attempts' => 2, 'reserved_at' => 1000006],
        ]));

        $jobs = $queue->allReservedJobs();

        $this->assertCount(2, $jobs);
        $this->assertInstanceOf(InspectedJob::class, $jobs->first());
        $this->assertSame('JobA', $jobs->first()->name);
        $this->assertSame('uuid-1', $jobs->first()->uuid);
        $this->assertSame(1, $jobs->first()->attempts);
        $this->assertInstanceOf(Carbon::class, $jobs->first()->createdAt);
        $this->assertSame(1000000, $jobs->first()->createdAt->getTimestamp());
        $this->assertSame('JobB', $jobs->last()->name);
        $this->assertSame('uuid-2', $jobs->last()->uuid);
        $this->assertSame(2, $jobs->last()->attempts);
    }

    public function testGetLockForPoppingIsCached()
    {
        $database = m::mock(Connection::class);
        $queue = new DatabaseQueue($database, 'table', 'default');

        $pdo = m::mock(\PDO::class);
        $pdo->shouldReceive('getAttribute')->with(\PDO::ATTR_DRIVER_NAME)->once()->andReturn('mysql');
        $pdo->shouldReceive('getAttribute')->with(\PDO::ATTR_SERVER_VERSION)->once()->andReturn('8.0.36');

        $database->shouldReceive('getPdo')->andReturn($pdo);
        $database->shouldReceive('getConfig')->with('version')->andReturn(null);

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
