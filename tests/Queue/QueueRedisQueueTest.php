<?php

use Mockery as m;

class QueueRedisQueueTest extends TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testPushProperlyPushesJobOntoRedis()
	{
		$queue = $this->getMock('Illuminate\Queue\RedisQueue', array('getRandomId'), array($redis = m::mock('Illuminate\Redis\Database'), 'default'));
		$queue->expects($this->once())->method('getRandomId')->will($this->returnValue('foo'));
		$redis->shouldReceive('connection')->once()->andReturn($redis);
		$redis->shouldReceive('rpush')->once()->with('queues:default', json_encode(array('job' => 'foo', 'data' => array('data'), 'id' => 'foo', 'attempts' => 1)));

		$id = $queue->push('foo', array('data'));
		$this->assertEquals('foo', $id);
	}


	public function testDelayedPushProperlyPushesJobOntoRedis()
	{
		$queue = $this->getMock('Illuminate\Queue\RedisQueue', array('getSeconds', 'getTime', 'getRandomId'), array($redis = m::mock('Illuminate\Redis\Database'), 'default'));
		$queue->expects($this->once())->method('getRandomId')->will($this->returnValue('foo'));
		$queue->expects($this->once())->method('getSeconds')->with(1)->will($this->returnValue(1));
		$queue->expects($this->once())->method('getTime')->will($this->returnValue(1));

		$redis->shouldReceive('connection')->once()->andReturn($redis);
		$redis->shouldReceive('zadd')->once()->with(
			'queues:default:delayed',
			2,
			json_encode(array('job' => 'foo', 'data' => array('data'), 'id' => 'foo', 'attempts' => 1))
		);

		$id = $queue->later(1, 'foo', array('data'));
		$this->assertEquals('foo', $id);
	}


	public function testDelayedPushWithDateTimeProperlyPushesJobOntoRedis()
	{
		$date = Carbon\Carbon::now();
		$queue = $this->getMock('Illuminate\Queue\RedisQueue', array('getSeconds', 'getTime', 'getRandomId'), array($redis = m::mock('Illuminate\Redis\Database'), 'default'));
		$queue->expects($this->once())->method('getRandomId')->will($this->returnValue('foo'));
		$queue->expects($this->once())->method('getSeconds')->with($date)->will($this->returnValue(1));
		$queue->expects($this->once())->method('getTime')->will($this->returnValue(1));

		$redis->shouldReceive('connection')->once()->andReturn($redis);
		$redis->shouldReceive('zadd')->once()->with(
			'queues:default:delayed',
			2,
			json_encode(array('job' => 'foo', 'data' => array('data'), 'id' => 'foo', 'attempts' => 1))
		);

		$queue->later($date, 'foo', array('data'));
	}


	public function testPopProperlyPopsJobOffOfRedis()
	{
		$queue = $this->getMock('Illuminate\Queue\RedisQueue', array('getTime', 'migrateAllExpiredJobs'), array($redis = m::mock('Illuminate\Redis\Database'), 'default'));
		$queue->setContainer(m::mock('Illuminate\Container\Container'));
		$queue->expects($this->once())->method('getTime')->will($this->returnValue(1));
		$queue->expects($this->once())->method('migrateAllExpiredJobs')->with($this->equalTo('queues:default'));

		$redis->shouldReceive('connection')->andReturn($redis);
		$redis->shouldReceive('lpop')->once()->with('queues:default')->andReturn('foo');
		$redis->shouldReceive('zadd')->once()->with('queues:default:reserved', 61, 'foo');

		$result = $queue->pop();

		$this->assertInstanceOf('Illuminate\Queue\Jobs\RedisJob', $result);
	}


	public function testReleaseMethod()
	{
		$queue = $this->getMock('Illuminate\Queue\RedisQueue', array('getTime'), array($redis = m::mock('Illuminate\Redis\Database'), 'default'));
		$queue->expects($this->once())->method('getTime')->will($this->returnValue(1));
		$redis->shouldReceive('connection')->once()->andReturn($redis);
		$redis->shouldReceive('zadd')->once()->with('queues:default:delayed', 2, json_encode(array('attempts' => 2)));

		$queue->release('default', json_encode(array('attempts' => 1)), 1, 2);
	}


	public function testMigrateExpiredJobs()
	{
		$queue = $this->getMock('Illuminate\Queue\RedisQueue', array('getTime'), array($redis = m::mock('Illuminate\Redis\Database'), 'default'));
		$queue->expects($this->once())->method('getTime')->will($this->returnValue(1));
		$transaction = m::mock('StdClass');
		$redis->shouldReceive('connection')->once()->andReturn($redis);
		$redis->shouldReceive('transaction')->with(m::any(), m::type('Closure'))->andReturnUsing(function($options, $callback) use ($transaction)
		{
			$callback($transaction);
		});
		$transaction->shouldReceive('zrangebyscore')->once()->with('from', '-inf', 1)->andReturn(array('foo', 'bar'));
		$transaction->shouldReceive('multi')->once();
		$transaction->shouldReceive('zremrangebyscore')->once()->with('from', '-inf', 1);
		$transaction->shouldReceive('rpush')->once()->with('to', 'foo', 'bar');

		$queue->migrateExpiredJobs('from', 'to');
	}


	public function testNotExpireJobsWhenExpireNull()
	{
		$queue = $this->getMock('Illuminate\Queue\RedisQueue', array('getTime', 'migrateAllExpiredJobs'), array($redis = m::mock('Illuminate\Redis\Database'), 'default', null));
		$redis->shouldReceive('connection')->andReturn($predis = m::mock('Predis\Client'));
		$queue->setContainer(m::mock('Illuminate\Container\Container'));
		$queue->setExpire(null);
		$queue->expects($this->once())->method('getTime')->will($this->returnValue(1));
		$queue->expects($this->never())->method('migrateAllExpiredJobs');
		$predis->shouldReceive('lpop')->once()->with('queues:default')->andReturn('foo');
		$predis->shouldReceive('zadd')->once()->with('queues:default:reserved', 1, 'foo');

		$result = $queue->pop();
	}


	public function testExpireJobsWhenExpireSet()
	{
		$queue = $this->getMock('Illuminate\Queue\RedisQueue', array('getTime', 'migrateAllExpiredJobs'), array($redis = m::mock('Illuminate\Redis\Database'), 'default', null));
		$redis->shouldReceive('connection')->andReturn($predis = m::mock('Predis\Client'));
		$queue->setContainer(m::mock('Illuminate\Container\Container'));
		$queue->setExpire(30);
		$queue->expects($this->once())->method('getTime')->will($this->returnValue(1));
		$queue->expects($this->once())->method('migrateAllExpiredJobs')->with($this->equalTo('queues:default'));
		$predis->shouldReceive('lpop')->once()->with('queues:default')->andReturn('foo');
		$predis->shouldReceive('zadd')->once()->with('queues:default:reserved', 31, 'foo');

		$result = $queue->pop();
	}

}
