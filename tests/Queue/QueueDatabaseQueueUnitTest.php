<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Queue\DatabaseQueue;
use Illuminate\Queue\Queue;
use Illuminate\Support\Str;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

class QueueDatabaseQueueUnitTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    /**
     * @dataProvider pushJobsDataProvider
     */
    public function testPushProperlyPushesJobOntoDatabase($uuid, $job, $displayNameStartsWith, $jobStartsWith)
    {
        Str::createUuidsUsing(function () use ($uuid) {
            return $uuid;
        });

        $queue = $this->getMockBuilder(DatabaseQueue::class)->onlyMethods(['currentTime'])->setConstructorArgs([$database = m::mock(Connection::class), 'table', 'default'])->getMock();
        $queue->expects($this->any())->method('currentTime')->willReturn('time');
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

        $container->shouldHaveReceived('bound')->with('events')->once();

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

        $queue = $this->getMockBuilder(DatabaseQueue::class)
            ->onlyMethods(['currentTime'])
            ->setConstructorArgs([$database = m::mock(Connection::class), 'table', 'default'])
            ->getMock();
        $queue->expects($this->any())->method('currentTime')->willReturn('time');
        $queue->setContainer($container = m::spy(Container::class));
        $database->shouldReceive('table')->with('table')->andReturn($query = m::mock(stdClass::class));
        $query->shouldReceive('insertGetId')->once()->andReturnUsing(function ($array) use ($uuid) {
            $this->assertSame('default', $array['queue']);
            $this->assertSame(json_encode(['uuid' => $uuid, 'displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'maxExceptions' => null, 'failOnTimeout' => false, 'backoff' => null, 'timeout' => null, 'data' => ['data']]), $array['payload']);
            $this->assertEquals(0, $array['attempts']);
            $this->assertNull($array['reserved_at']);
            $this->assertIsInt($array['available_at']);
        });

        $queue->later(10, 'foo', ['data']);

        $container->shouldHaveReceived('bound')->with('events')->once();

        Str::createUuidsNormally();
    }

    public function testFailureToCreatePayloadFromObject()
    {
        $this->expectException('InvalidArgumentException');

        $job = new stdClass;
        $job->invalid = "\xc3\x28";

        $queue = $this->getMockForAbstractClass(Queue::class);
        $class = new ReflectionClass(Queue::class);

        $createPayload = $class->getMethod('createPayload');
        $createPayload->setAccessible(true);
        $createPayload->invokeArgs($queue, [
            $job,
            'queue-name',
        ]);
    }

    public function testFailureToCreatePayloadFromArray()
    {
        $this->expectException('InvalidArgumentException');

        $queue = $this->getMockForAbstractClass(Queue::class);
        $class = new ReflectionClass(Queue::class);

        $createPayload = $class->getMethod('createPayload');
        $createPayload->setAccessible(true);
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

        $database = m::mock(Connection::class);
        $queue = $this->getMockBuilder(DatabaseQueue::class)->onlyMethods(['currentTime', 'availableAt'])->setConstructorArgs([$database, 'table', 'default'])->getMock();
        $queue->expects($this->any())->method('currentTime')->willReturn('created');
        $queue->expects($this->any())->method('availableAt')->willReturn('available');
        $database->shouldReceive('table')->with('table')->andReturn($query = m::mock(stdClass::class));
        $query->shouldReceive('insert')->once()->andReturnUsing(function ($records) use ($uuid) {
            $this->assertEquals([[
                'queue' => 'queue',
                'payload' => json_encode(['uuid' => $uuid, 'displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'maxExceptions' => null, 'failOnTimeout' => false, 'backoff' => null, 'timeout' => null, 'data' => ['data']]),
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => 'available',
                'created_at' => 'created',
            ], [
                'queue' => 'queue',
                'payload' => json_encode(['uuid' => $uuid, 'displayName' => 'bar', 'job' => 'bar', 'maxTries' => null, 'maxExceptions' => null, 'failOnTimeout' => false, 'backoff' => null, 'timeout' => null, 'data' => ['data']]),
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => 'available',
                'created_at' => 'created',
            ]], $records);
        });

        $queue->bulk(['foo', 'bar'], ['data'], 'queue');

        Str::createUuidsNormally();
    }

    public function testBuildDatabaseRecordWithPayloadAtTheEnd()
    {
        $queue = m::mock(DatabaseQueue::class);
        $record = $queue->buildDatabaseRecord('queue', 'any_payload', 0);
        $this->assertArrayHasKey('payload', $record);
        $this->assertArrayHasKey('payload', array_slice($record, -1, 1, true));
    }
}

class MyTestJob
{
    public function handle()
    {
        // ...
    }
}
