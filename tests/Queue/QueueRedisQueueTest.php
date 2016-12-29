<?php

use Mockery as m;

class QueueRedisQueueTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testPushProperlyPushesJobOntoRedis()
    {
        $queue = $this->getMockBuilder('Illuminate\Queue\RedisQueue')->setMethods(['getRandomId'])->setConstructorArgs([$redis = m::mock('Illuminate\Contracts\Redis\Factory'), 'default'])->getMock();
        $queue->expects($this->once())->method('getRandomId')->will($this->returnValue('foo'));
        $redis->shouldReceive('connection')->once()->andReturn($redis);
        $redis->shouldReceive('rpush')->once()->with('queues:default', json_encode(['job' => 'foo', 'data' => ['data'], 'id' => 'foo', 'attempts' => 1]));

        $id = $queue->push('foo', ['data']);
        $this->assertEquals('foo', $id);
    }

    public function testDelayedPushProperlyPushesJobOntoRedis()
    {
        $queue = $this->getMockBuilder('Illuminate\Queue\RedisQueue')->setMethods(['secondsUntil', 'currentTime', 'getRandomId'])->setConstructorArgs([$redis = m::mock('Illuminate\Contracts\Redis\Factory'), 'default'])->getMock();
        $queue->expects($this->once())->method('getRandomId')->will($this->returnValue('foo'));
        $queue->expects($this->once())->method('secondsUntil')->with(1)->will($this->returnValue(1));
        $queue->expects($this->once())->method('currentTime')->will($this->returnValue(1));

        $redis->shouldReceive('connection')->once()->andReturn($redis);
        $redis->shouldReceive('zadd')->once()->with(
            'queues:default:delayed',
            2,
            json_encode(['job' => 'foo', 'data' => ['data'], 'id' => 'foo', 'attempts' => 1])
        );

        $id = $queue->later(1, 'foo', ['data']);
        $this->assertEquals('foo', $id);
    }

    public function testDelayedPushWithDateTimeProperlyPushesJobOntoRedis()
    {
        $date = Carbon\Carbon::now();
        $queue = $this->getMockBuilder('Illuminate\Queue\RedisQueue')->setMethods(['secondsUntil', 'currentTime', 'getRandomId'])->setConstructorArgs([$redis = m::mock('Illuminate\Contracts\Redis\Factory'), 'default'])->getMock();
        $queue->expects($this->once())->method('getRandomId')->will($this->returnValue('foo'));
        $queue->expects($this->once())->method('secondsUntil')->with($date)->will($this->returnValue(1));
        $queue->expects($this->once())->method('currentTime')->will($this->returnValue(1));

        $redis->shouldReceive('connection')->once()->andReturn($redis);
        $redis->shouldReceive('zadd')->once()->with(
            'queues:default:delayed',
            2,
            json_encode(['job' => 'foo', 'data' => ['data'], 'id' => 'foo', 'attempts' => 1])
        );

        $queue->later($date, 'foo', ['data']);
    }
}
