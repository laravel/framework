<?php

use Mockery as m;

class QueueDatabaseQueueTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testPushProperlyPushesJobOntoDatabase()
    {
        $queue = $this->getMock('Illuminate\Queue\DatabaseQueue', ['getTime'], [$database = m::mock('Illuminate\Database\Connection'), 'table', 'default']);
        $queue->expects($this->any())->method('getTime')->will($this->returnValue('time'));
        $database->shouldReceive('table')->with('table')->andReturn($query = m::mock('StdClass'));
        $query->shouldReceive('insertGetId')->once()->andReturnUsing(function ($array) {
            $this->assertEquals('default', $array['queue']);
            $this->assertEquals(json_encode(['job' => 'foo', 'data' => ['data']]), $array['payload']);
            $this->assertEquals(0, $array['attempts']);
            $this->assertEquals(0, $array['reserved']);
            $this->assertNull($array['reserved_at']);
            $this->assertTrue(is_int($array['available_at']));
        });

        $queue->push('foo', ['data']);
    }

    public function testDelayedPushProperlyPushesJobOntoDatabase()
    {
        $queue = $this->getMock(
            'Illuminate\Queue\DatabaseQueue',
            ['getTime'],
            [$database = m::mock('Illuminate\Database\Connection'), 'table', 'default']
        );
        $queue->expects($this->any())->method('getTime')->will($this->returnValue('time'));
        $database->shouldReceive('table')->with('table')->andReturn($query = m::mock('StdClass'));
        $query->shouldReceive('insertGetId')->once()->andReturnUsing(function ($array) {
            $this->assertEquals('default', $array['queue']);
            $this->assertEquals(json_encode(['job' => 'foo', 'data' => ['data']]), $array['payload']);
            $this->assertEquals(0, $array['attempts']);
            $this->assertEquals(0, $array['reserved']);
            $this->assertNull($array['reserved_at']);
            $this->assertTrue(is_int($array['available_at']));
        });

        $queue->later(10, 'foo', ['data']);
    }

    public function testBulkBatchPushesOntoDatabase()
    {
        $database = m::mock('Illuminate\Database\Connection');
        $queue = $this->getMock('Illuminate\Queue\DatabaseQueue', ['getTime', 'getAvailableAt'], [$database, 'table', 'default']);
        $queue->expects($this->any())->method('getTime')->will($this->returnValue('created'));
        $queue->expects($this->any())->method('getAvailableAt')->will($this->returnValue('available'));
        $database->shouldReceive('table')->with('table')->andReturn($query = m::mock('StdClass'));
        $query->shouldReceive('insert')->once()->andReturnUsing(function ($records) {
            $this->assertEquals([[
                'queue' => 'queue',
                'payload' => json_encode(['job' => 'foo', 'data' => ['data']]),
                'attempts' => 0,
                'reserved' => 0,
                'reserved_at' => null,
                'available_at' => 'available',
                'created_at' => 'created',
            ], [
                'queue' => 'queue',
                'payload' => json_encode(['job' => 'bar', 'data' => ['data']]),
                'attempts' => 0,
                'reserved' => 0,
                'reserved_at' => null,
                'available_at' => 'available',
                'created_at' => 'created',
            ]], $records);
        });

        $queue->bulk(['foo', 'bar'], ['data'], 'queue');
    }

    public function testSchedulerUpdatesExpiredJobsWhenNeeded()
    {
        $queue = $this->getMock('Illuminate\Queue\DatabaseQueue', ['getNextAvailableJob'], [$database = m::mock('Illuminate\Database\Connection'), 'table', 'default', 1]);
        $queue->expects($this->any())->method('getNextAvailableJob')->will($this->returnValue(null));
        $database->shouldReceive('commit');
        $database->shouldReceive('table')->with('table')->andReturn($query = m::mock('StdClass'));
        $query->shouldReceive('where')->andReturn($query);
        $mockJobs = [];
        for ($i = 0; $i < 3; ++$i) {
            $mockJobs[$i] = new StdClass();
            $mockJobs[$i]->id = $i + 1;
        }
        $query->shouldReceive('get')->andReturn($mockJobs);
        $query->shouldReceive('select')->once()->andReturnUsing(function ($array) use ($query) {
            $this->assertEquals('id', $array[0]);

            return $query;
        });
        $query->shouldReceive('whereIn')->once()->andReturnUsing(function ($column, $values) use ($query, $mockJobs) {
            $this->assertEquals('id', $column);
            foreach ($values as $idx => $v) {
                $this->assertEquals($mockJobs[$idx]->id, $v);
            }

            return $query;
        });
        $query->shouldReceive('update')->once()->andReturnUsing(function ($array) use ($query) {
            $this->assertEquals(0, $array['reserved']);
            $this->assertEquals(null, $array['reserved_at']);
            $this->assertEquals('attempts + 1', $array['attempts']->getValue());

            return $query;
        });

        $queue->pop();
    }

    public function testSchedulerDoesNotTryToExpireJobsWhenNotNeeded()
    {
        $queue = $this->getMock('Illuminate\Queue\DatabaseQueue', ['getNextAvailableJob'], [$database = m::mock('Illuminate\Database\Connection'), 'table', 'default', 1]);
        $queue->expects($this->any())->method('getNextAvailableJob')->will($this->returnValue(null));
        $database->shouldReceive('table')->with('table')->andReturn($query = m::mock('StdClass'));
        $database->shouldReceive('commit');
        $query->shouldReceive('where')->andReturn($query);
        $query->shouldReceive('get')->andReturn(null);
        $query->shouldReceive('select')->once()->andReturnUsing(function ($array) use ($query) {
            $this->assertEquals('id', $array[0]);

            return $query;
        });
        $query->shouldReceive('whereIn')->never();
        $query->shouldReceive('update')->never();

        $queue->pop();
    }
}
