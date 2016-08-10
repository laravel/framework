<?php

use Mockery as m;

class QueueDatabaseQueueUnitTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testPushProperlyPushesJobOntoDatabase()
    {
        $queue = $this->getMockBuilder('Illuminate\Queue\DatabaseQueue')->setMethods(['getTime'])->setConstructorArgs([$database = m::mock('Illuminate\Database\Connection'), 'table', 'default'])->getMock();
        $queue->expects($this->any())->method('getTime')->will($this->returnValue('time'));
        $database->shouldReceive('table')->with('table')->andReturn($query = m::mock('StdClass'));
        $query->shouldReceive('insertGetId')->once()->andReturnUsing(function ($array) {
            $this->assertEquals('default', $array['queue']);
            $this->assertEquals(json_encode(['job' => 'foo', 'data' => ['data']]), $array['payload']);
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
            ['getTime'])->setConstructorArgs(
            [$database = m::mock('Illuminate\Database\Connection'), 'table', 'default']
        )->getMock();
        $queue->expects($this->any())->method('getTime')->will($this->returnValue('time'));
        $database->shouldReceive('table')->with('table')->andReturn($query = m::mock('StdClass'));
        $query->shouldReceive('insertGetId')->once()->andReturnUsing(function ($array) {
            $this->assertEquals('default', $array['queue']);
            $this->assertEquals(json_encode(['job' => 'foo', 'data' => ['data']]), $array['payload']);
            $this->assertEquals(0, $array['attempts']);
            $this->assertNull($array['reserved_at']);
            $this->assertInternalType('int', $array['available_at']);
        });

        $queue->later(10, 'foo', ['data']);
    }

    public function testBulkBatchPushesOntoDatabase()
    {
        $database = m::mock('Illuminate\Database\Connection');
        $queue = $this->getMockBuilder('Illuminate\Queue\DatabaseQueue')->setMethods(['getTime', 'getAvailableAt'])->setConstructorArgs([$database, 'table', 'default'])->getMock();
        $queue->expects($this->any())->method('getTime')->will($this->returnValue('created'));
        $queue->expects($this->any())->method('getAvailableAt')->will($this->returnValue('available'));
        $database->shouldReceive('table')->with('table')->andReturn($query = m::mock('StdClass'));
        $query->shouldReceive('insert')->once()->andReturnUsing(function ($records) {
            $this->assertEquals([[
                'queue' => 'queue',
                'payload' => json_encode(['job' => 'foo', 'data' => ['data']]),
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => 'available',
                'created_at' => 'created',
            ], [
                'queue' => 'queue',
                'payload' => json_encode(['job' => 'bar', 'data' => ['data']]),
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => 'available',
                'created_at' => 'created',
            ]], $records);
        });

        $queue->bulk(['foo', 'bar'], ['data'], 'queue');
    }
}
