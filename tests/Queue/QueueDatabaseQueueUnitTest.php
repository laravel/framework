<?php

namespace Illuminate\Tests\Queue;

use stdClass;
use Mockery as m;
use ReflectionClass;
use PHPUnit\Framework\TestCase;

class QueueDatabaseQueueUnitTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testPushProperlyPushesJobOntoDatabase()
    {
        $queue = $this->getMockBuilder('Illuminate\Queue\DatabaseQueue')->setMethods(['currentTime'])->setConstructorArgs([$database = m::mock('Illuminate\Database\Connection'), 'table', 'default'])->getMock();
        $queue->expects($this->any())->method('currentTime')->will($this->returnValue('time'));
        $database->shouldReceive('table')->with('table')->andReturn($query = m::mock('stdClass'));
        $query->shouldReceive('insertGetId')->once()->andReturnUsing(function ($array) {
            $this->assertEquals('default', $array['queue']);
            $this->assertEquals(json_encode(['displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'timeout' => null, 'data' => ['data']]), $array['payload']);
            $this->assertEquals(0, $array['attempts']);
            $this->assertNull($array['reserved_at']);
            $this->assertInternalType('int', $array['available_at']);
        });

        $queue->push('foo', ['data']);
    }

    public function testDelayedPushProperlyPushesJobOntoDatabase()
    {
        $queue = $this->getMockBuilder(
            'Illuminate\Queue\DatabaseQueue')->setMethods(
            ['currentTime'])->setConstructorArgs(
            [$database = m::mock('Illuminate\Database\Connection'), 'table', 'default']
        )->getMock();
        $queue->expects($this->any())->method('currentTime')->will($this->returnValue('time'));
        $database->shouldReceive('table')->with('table')->andReturn($query = m::mock('stdClass'));
        $query->shouldReceive('insertGetId')->once()->andReturnUsing(function ($array) {
            $this->assertEquals('default', $array['queue']);
            $this->assertEquals(json_encode(['displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'timeout' => null, 'data' => ['data']]), $array['payload']);
            $this->assertEquals(0, $array['attempts']);
            $this->assertNull($array['reserved_at']);
            $this->assertInternalType('int', $array['available_at']);
        });

        $queue->later(10, 'foo', ['data']);
    }

    public function testFailureToCreatePayloadFromObject()
    {
        $this->expectException('InvalidArgumentException');

        $job = new stdClass;
        $job->invalid = "\xc3\x28";

        $queue = $this->getMockForAbstractClass('Illuminate\Queue\Queue');
        $class = new ReflectionClass('Illuminate\Queue\Queue');

        $createPayload = $class->getMethod('createPayload');
        $createPayload->setAccessible(true);
        $createPayload->invokeArgs($queue, [
            $job,
        ]);
    }

    public function testFailureToCreatePayloadFromArray()
    {
        $this->expectException('InvalidArgumentException');

        $queue = $this->getMockForAbstractClass('Illuminate\Queue\Queue');
        $class = new ReflectionClass('Illuminate\Queue\Queue');

        $createPayload = $class->getMethod('createPayload');
        $createPayload->setAccessible(true);
        $createPayload->invokeArgs($queue, [
            ["\xc3\x28"],
        ]);
    }

    public function testBulkBatchPushesOntoDatabase()
    {
        $database = m::mock('Illuminate\Database\Connection');
        $queue = $this->getMockBuilder('Illuminate\Queue\DatabaseQueue')->setMethods(['currentTime', 'availableAt'])->setConstructorArgs([$database, 'table', 'default'])->getMock();
        $queue->expects($this->any())->method('currentTime')->will($this->returnValue('created'));
        $queue->expects($this->any())->method('availableAt')->will($this->returnValue('available'));
        $database->shouldReceive('table')->with('table')->andReturn($query = m::mock('stdClass'));
        $query->shouldReceive('insert')->once()->andReturnUsing(function ($records) {
            $this->assertEquals([[
                'queue' => 'queue',
                'payload' => json_encode(['displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'timeout' => null, 'data' => ['data']]),
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => 'available',
                'created_at' => 'created',
            ], [
                'queue' => 'queue',
                'payload' => json_encode(['displayName' => 'bar', 'job' => 'bar', 'maxTries' => null, 'timeout' => null, 'data' => ['data']]),
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => 'available',
                'created_at' => 'created',
            ]], $records);
        });

        $queue->bulk(['foo', 'bar'], ['data'], 'queue');
    }

    public function testBuildDatabaseRecordWithPayloadAtTheEnd()
    {
        $queue = m::mock('Illuminate\Queue\DatabaseQueue');
        $record = $queue->buildDatabaseRecord('queue', 'any_payload', 0);
        $this->assertArrayHasKey('payload', $record);
        $this->assertArrayHasKey('payload', array_slice($record, -1, 1, true));
    }
}
