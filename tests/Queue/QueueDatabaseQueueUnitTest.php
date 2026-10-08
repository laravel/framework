<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Bus\Batchable;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\CountCrashesAsExceptions;
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
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use JMac\Testing\Matching\Argument;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

class QueueDatabaseQueueUnitTest extends TestCase
{
    use VerifiesDoubles;

    #[DataProvider('pushJobsDataProvider')]
    #[AllowMockObjectsWithoutExpectations]
    public function testPushProperlyPushesJobOntoDatabase($uuid, $job, $displayNameStartsWith, $jobStartsWith)
    {
        Str::createUuidsUsing(function () use ($uuid) {
            return $uuid;
        });

        $database = Double::for(Connection::class);
        $queue = $this->getMockBuilder(DatabaseQueue::class)->onlyMethods(['currentTime'])->setConstructorArgs([$database, 'table', 'default'])->getMock();
        $queue->method('currentTime')->willReturn('time');
        $container = Double::for(Container::class, override: true);
        $queue->setContainer($container->instance());
        $query = Double::for(QueryBuilder::class);
        $database->expects('table')->with('table')->returns($query);
        $query->expects('insertGetId')->resolves(function ($array) use ($uuid, $displayNameStartsWith, $jobStartsWith) {
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

        $container->received('bound')->with('events')->times(2);

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

    #[AllowMockObjectsWithoutExpectations]
    public function testDelayedPushProperlyPushesJobOntoDatabase()
    {
        $uuid = Str::uuid();

        Str::createUuidsUsing(function () use ($uuid) {
            return $uuid;
        });

        $time = Carbon::now();
        Carbon::setTestNow($time);

        $database = Double::for(Connection::class);
        $queue = $this->getMockBuilder(DatabaseQueue::class)
            ->onlyMethods(['currentTime'])
            ->setConstructorArgs([$database, 'table', 'default'])
            ->getMock();
        $queue->method('currentTime')->willReturn('time');
        $container = Double::for(Container::class, override: true);
        $queue->setContainer($container->instance());
        $query = Double::for(QueryBuilder::class);
        $database->expects('table')->with('table')->returns($query);
        $query->expects('insertGetId')->resolves(function ($array) use ($uuid, $time) {
            $this->assertSame('default', $array['queue']);
            $this->assertSame(json_encode(['uuid' => $uuid, 'displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'maxExceptions' => null, 'failOnTimeout' => false, 'backoff' => null, 'timeout' => null, 'data' => ['data'], 'createdAt' => $time->getTimestamp(), 'delay' => 10]), $array['payload']);
            $this->assertEquals(0, $array['attempts']);
            $this->assertNull($array['reserved_at']);
            $this->assertIsInt($array['available_at']);
        });

        $queue->later(10, 'foo', ['data']);

        $container->received('bound')->with('events')->times(2);

        Str::createUuidsNormally();
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testPushIncludesBatchIdInPayloadForBatchableJob()
    {
        $uuid = Str::uuid()->toString();

        Str::createUuidsUsing(function () use ($uuid) {
            return $uuid;
        });

        $job = (new MyBatchableJob)->withBatchId('test-batch-id');

        $database = Double::for(Connection::class);
        $queue = $this->getMockBuilder(DatabaseQueue::class)->onlyMethods(['currentTime'])->setConstructorArgs([$database, 'table', 'default'])->getMock();
        $queue->method('currentTime')->willReturn('time');
        $container = Double::for(Container::class, override: true);
        $queue->setContainer($container->instance());
        $query = Double::for(QueryBuilder::class);
        $database->expects('table')->with('table')->returns($query);
        $query->expects('insertGetId')->resolves(function ($array) {
            $payload = json_decode($array['payload'], true);
            $this->assertSame('test-batch-id', $payload['data']['batchId']);
        });

        $queue->push($job, ['data']);

        $container->received('bound')->with('events')->times(2);

        Str::createUuidsNormally();
    }

    public function testPushUsesPropertiesDeclaredOnChildClassOverInheritedAttributes()
    {
        $database = Double::for(Connection::class);
        $queue = new DatabaseQueue($database, 'table', 'default');
        $container = Double::for(Container::class, override: true);
        $queue->setContainer($container->instance());
        $query = Double::for(QueryBuilder::class);
        $database->expects('table')->with('table')->returns($query);
        $query->expects('insertGetId')->resolves(function ($array) {
            $payload = json_decode($array['payload'], true);

            $this->assertSame(1700, $payload['timeout']);
            $this->assertSame(7, $payload['maxTries']);
            $this->assertSame('13', $payload['backoff']);
            $this->assertSame(11, $payload['maxExceptions']);
            $this->assertFalse($payload['failOnTimeout']);
            $this->assertFalse($payload['countCrashesAsExceptions']);
        });

        $queue->push(new ChildJobWithPropertiesOverridingParentAttributes, ['data']);

        $container->received('bound')->with('events')->times(2);
    }

    public function testPushStillUsesAttributesDeclaredOnSameClassOverDefaultProperties()
    {
        $database = Double::for(Connection::class);
        $queue = new DatabaseQueue($database, 'table', 'default');
        $container = Double::for(Container::class, override: true);
        $queue->setContainer($container->instance());
        $query = Double::for(QueryBuilder::class);
        $database->expects('table')->with('table')->returns($query);
        $query->expects('insertGetId')->resolves(function ($array) {
            $payload = json_decode($array['payload'], true);

            $this->assertSame(40, $payload['timeout']);
            $this->assertSame(2, $payload['maxTries']);
            $this->assertSame('9', $payload['backoff']);
            $this->assertSame(3, $payload['maxExceptions']);
            $this->assertTrue($payload['failOnTimeout']);
            $this->assertTrue($payload['countCrashesAsExceptions']);
        });

        $queue->push(new JobWithAttributesAndDefaultProperties, ['data']);

        $container->received('bound')->with('events')->times(2);
    }

    public function testFailureToCreatePayloadFromObject()
    {
        $this->expectException('InvalidArgumentException');

        $job = new stdClass;
        $job->invalid = "\xc3\x28";

        $queue = new DatabaseQueue(Double::for(Connection::class), 'table', 'default');
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

        $queue = new DatabaseQueue(Double::for(Connection::class), 'table', 'default');
        $class = new ReflectionClass(Queue::class);

        $createPayload = $class->getMethod('createPayload');
        $createPayload->invokeArgs($queue, [
            ["\xc3\x28"],
            'queue-name',
        ]);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testBulkBatchPushesOntoDatabase()
    {
        $uuid = Str::uuid();

        Str::createUuidsUsing(function () use ($uuid) {
            return $uuid;
        });

        $time = Carbon::now();
        Carbon::setTestNow($time);

        $database = Double::for(Connection::class);
        $queue = $this->getMockBuilder(DatabaseQueue::class)->onlyMethods(['currentTime', 'availableAt'])->setConstructorArgs([$database, 'table', 'default'])->getMock();
        $queue->method('currentTime')->willReturn('created');
        $queue->method('availableAt')->willReturn('available');
        $query = Double::for(QueryBuilder::class);
        $database->expects('table')->with('table')->returns($query);
        $query->expects('insert')->resolves(function ($records) use ($uuid, $time) {
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

        Str::createUuidsNormally();
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testDelayAttributeIsRespectedWhenBulkPushing()
    {
        $database = Double::for(Connection::class);
        $queue = $this->getMockBuilder(DatabaseQueue::class)->onlyMethods(['currentTime', 'availableAt'])->setConstructorArgs([$database, 'table', 'default'])->getMock();
        $queue->method('currentTime')->willReturn('created');
        $queue->method('availableAt')->willReturnCallback(function ($delay = 0) {
            return 'available:'.$delay;
        });
        $query = Double::for(QueryBuilder::class);
        $database->expects('table')->with('table')->returns($query);
        $query->expects('insert')->resolves(function ($records) {
            $this->assertSame('available:15', $records[0]['available_at']);
        });

        $queue->bulk([new JobWithDelayAttribute], ['data'], 'queue');
    }

    public function testBulkDefersAfterCommitJobsUntilTheTransactionCommits()
    {
        $transactions = Double::for(\Illuminate\Database\DatabaseTransactionsManager::class);

        $committed = null;

        $transactions->expects('addCallback')->resolves(function ($callback) use (&$committed) {
            $committed = $callback;
        });

        $container = new Container;
        $container->instance('db.transactions', $transactions);

        $database = Double::for(Connection::class);
        $queue = new DatabaseQueue($database, 'table', 'default');
        $queue->setContainer($container);

        $inserted = false;

        $query = Double::for(QueryBuilder::class);
        $database->expects('table')->with('table')->returns($query);
        $query->expects('insert')->resolves(function () use (&$inserted) {
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
        $queue = new DatabaseQueue(Double::for(Connection::class), 'table', 'default');
        $class = new ReflectionClass(DatabaseQueue::class);
        $record = $class->getMethod('buildDatabaseRecord')->invoke($queue, 'queue', 'any_payload', 0);
        $this->assertArrayHasKey('payload', $record);
        $this->assertArrayHasKey('payload', array_slice($record, -1, 1, true));
    }

    public function testPendingJobs()
    {
        $database = Double::for(Connection::class);
        $queue = new DatabaseQueue($database, 'table', 'default');
        $queue->setContainer(new Container);

        $payload = json_encode(['uuid' => 'test-uuid', 'displayName' => 'MyTestJob', 'job' => 'foo', 'data' => [], 'createdAt' => 1000000]);

        $query = Double::for(QueryBuilder::class);
        $database->expects('table')->with('table')->returns($query);
        $query->expects('where')->with('queue', 'default')->returns($query);
        $query->expects('whereNull')->with('reserved_at')->returns($query);
        $query->expects('where')->with('available_at', '<=', Argument::any())->returns($query);
        $query->expects('get')->returns(collect([(object) ['id' => 1, 'queue' => 'default', 'payload' => $payload, 'attempts' => 0, 'reserved_at' => null]]));

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
        $database = Double::for(Connection::class);
        $queue = new DatabaseQueue($database, 'table', 'default');
        $queue->setContainer(new Container);

        $payload = json_encode(['uuid' => 'test-uuid', 'displayName' => 'MyDelayedJob', 'job' => 'foo', 'data' => [], 'createdAt' => 1000000]);

        $query = Double::for(QueryBuilder::class);
        $database->expects('table')->with('table')->returns($query);
        $query->expects('where')->with('queue', 'default')->returns($query);
        $query->expects('whereNull')->with('reserved_at')->returns($query);
        $query->expects('where')->with('available_at', '>', Argument::any())->returns($query);
        $query->expects('get')->returns(collect([(object) ['id' => 2, 'queue' => 'default', 'payload' => $payload, 'attempts' => 0, 'reserved_at' => null]]));

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
        $database = Double::for(Connection::class);
        $queue = new DatabaseQueue($database, 'table', 'default');
        $queue->setContainer(new Container);

        $payload = json_encode(['uuid' => 'test-uuid', 'displayName' => 'MyTestJob', 'job' => 'foo', 'data' => [], 'createdAt' => 1000000]);

        $query = Double::for(QueryBuilder::class);
        $database->expects('table')->with('table')->returns($query);
        $query->expects('where')->with('queue', 'default')->returns($query);
        $query->expects('whereNotNull')->with('reserved_at')->returns($query);
        $query->expects('get')->returns(collect([(object) ['id' => 1, 'queue' => 'default', 'payload' => $payload, 'attempts' => 1, 'reserved_at' => Carbon::now()->getTimestamp()]]));

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
        $database = Double::for(Connection::class);
        $queue = new DatabaseQueue($database, 'table', 'default');
        $queue->setContainer(new Container);

        $payload1 = json_encode(['uuid' => 'uuid-1', 'displayName' => 'JobA', 'job' => 'foo', 'data' => [], 'createdAt' => 1000000]);
        $payload2 = json_encode(['uuid' => 'uuid-2', 'displayName' => 'JobB', 'job' => 'foo', 'data' => [], 'createdAt' => 1000001]);

        $query = Double::for(QueryBuilder::class);
        $database->expects('table')->with('table')->returns($query);
        $query->expects('whereNull')->with('reserved_at')->returns($query);
        $query->expects('where')->with('available_at', '<=', Argument::any())->returns($query);
        $query->expects('get')->returns(collect([
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
        $database = Double::for(Connection::class);
        $queue = new DatabaseQueue($database, 'table', 'default');
        $queue->setContainer(new Container);

        $payload1 = json_encode(['uuid' => 'uuid-1', 'displayName' => 'JobA', 'job' => 'foo', 'data' => [], 'createdAt' => 1000000]);
        $payload2 = json_encode(['uuid' => 'uuid-2', 'displayName' => 'JobB', 'job' => 'foo', 'data' => [], 'createdAt' => 1000001]);

        $query = Double::for(QueryBuilder::class);
        $database->expects('table')->with('table')->returns($query);
        $query->expects('whereNull')->with('reserved_at')->returns($query);
        $query->expects('where')->with('available_at', '>', Argument::any())->returns($query);
        $query->expects('get')->returns(collect([
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
        $database = Double::for(Connection::class);
        $queue = new DatabaseQueue($database, 'table', 'default');
        $queue->setContainer(new Container);

        $payload1 = json_encode(['uuid' => 'uuid-1', 'displayName' => 'JobA', 'job' => 'foo', 'data' => [], 'createdAt' => 1000000]);
        $payload2 = json_encode(['uuid' => 'uuid-2', 'displayName' => 'JobB', 'job' => 'foo', 'data' => [], 'createdAt' => 1000001]);

        $query = Double::for(QueryBuilder::class);
        $database->expects('table')->with('table')->returns($query);
        $query->expects('whereNotNull')->with('reserved_at')->returns($query);
        $query->expects('get')->returns(collect([
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

    public function testTotalSize()
    {
        $database = Double::for(Connection::class);
        $queue = new DatabaseQueue($database, 'table', 'default');
        $queue->setContainer(new Container);

        $query = Double::for(QueryBuilder::class);
        $database->expects('table')->with('table')->returns($query);
        $query->expects('count')->returns(9);

        $this->assertSame(9, $queue->totalSize());
    }

    public function testTotalPendingSize()
    {
        $database = Double::for(Connection::class);
        $queue = new DatabaseQueue($database, 'table', 'default');
        $queue->setContainer(new Container);

        $query = Double::for(QueryBuilder::class);
        $database->expects('table')->with('table')->returns($query);
        $query->expects('whereNull')->with('reserved_at')->returns($query);
        $query->expects('where')->with('available_at', '<=', Argument::any())->returns($query);
        $query->expects('count')->returns(2);

        $this->assertSame(2, $queue->totalPendingSize());
    }

    public function testTotalDelayedSize()
    {
        $database = Double::for(Connection::class);
        $queue = new DatabaseQueue($database, 'table', 'default');
        $queue->setContainer(new Container);

        $query = Double::for(QueryBuilder::class);
        $database->expects('table')->with('table')->returns($query);
        $query->expects('whereNull')->with('reserved_at')->returns($query);
        $query->expects('where')->with('available_at', '>', Argument::any())->returns($query);
        $query->expects('count')->returns(3);

        $this->assertSame(3, $queue->totalDelayedSize());
    }

    public function testTotalReservedSize()
    {
        $database = Double::for(Connection::class);
        $queue = new DatabaseQueue($database, 'table', 'default');
        $queue->setContainer(new Container);

        $query = Double::for(QueryBuilder::class);
        $database->expects('table')->with('table')->returns($query);
        $query->expects('whereNotNull')->with('reserved_at')->returns($query);
        $query->expects('count')->returns(4);

        $this->assertSame(4, $queue->totalReservedSize());
    }

    public function testGetLockForPoppingIsCached()
    {
        $database = Double::for(Connection::class);
        $queue = new DatabaseQueue($database, 'table', 'default');

        $pdo = Double::for(\PDO::class);
        $pdo->expects('getAttribute')->with(\PDO::ATTR_DRIVER_NAME)->returns('mysql');
        $pdo->expects('getAttribute')->with(\PDO::ATTR_SERVER_VERSION)->returns('8.0.36');

        $database->expects('getPdo')->times(2)->returns($pdo);
        $database->expects('getConfig')->with('version')->returns(null);

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
#[CountCrashesAsExceptions]
#[FailOnTimeout]
#[MaxExceptions(3)]
#[Timeout(40)]
#[Tries(2)]
abstract class ParentJobWithAttributes implements ShouldQueue
{
}

class ChildJobWithPropertiesOverridingParentAttributes extends ParentJobWithAttributes
{
    public $backoff = 13;

    public $countCrashesAsExceptions = false;

    public $failOnTimeout = false;

    public $maxExceptions = 11;

    public $timeout = 1700;

    public $tries = 7;
}

#[Backoff(9)]
#[CountCrashesAsExceptions]
#[FailOnTimeout]
#[MaxExceptions(3)]
#[Timeout(40)]
#[Tries(2)]
class JobWithAttributesAndDefaultProperties implements ShouldQueue
{
    public $backoff = 13;

    public $countCrashesAsExceptions = false;

    public $failOnTimeout = false;

    public $maxExceptions = 11;

    public $timeout = 1700;

    public $tries = 7;
}
