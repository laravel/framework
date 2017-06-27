<?php

namespace Illuminate\Tests\Queue;

use Mockery as m;
use PHPUnit\Framework\TestCase;

class QueueRedisQueueTest extends TestCase
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
        $redis->shouldReceive('rpush')->once()->with('queues:default', json_encode(['displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'timeout' => null, 'data' => ['data'], 'id' => 'foo', 'attempts' => 0]));

        $id = $queue->push('foo', ['data']);
        $this->assertEquals('foo', $id);
    }

    public function testDelayedPushProperlyPushesJobOntoRedis()
    {
        $queue = $this->getMockBuilder('Illuminate\Queue\RedisQueue')->setMethods(['availableAt', 'getRandomId'])->setConstructorArgs([$redis = m::mock('Illuminate\Contracts\Redis\Factory'), 'default'])->getMock();
        $queue->expects($this->once())->method('getRandomId')->will($this->returnValue('foo'));
        $queue->expects($this->once())->method('availableAt')->with(1)->will($this->returnValue(2));

        $redis->shouldReceive('connection')->once()->andReturn($redis);
        $redis->shouldReceive('zadd')->once()->with(
            'queues:default:delayed',
            2,
            json_encode(['displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'timeout' => null, 'data' => ['data'], 'id' => 'foo', 'attempts' => 0])
        );

        $id = $queue->later(1, 'foo', ['data']);
        $this->assertEquals('foo', $id);
    }

    public function testDelayedPushWithDateTimeProperlyPushesJobOntoRedis()
    {
        $date = \Illuminate\Support\Carbon::now();
        $queue = $this->getMockBuilder('Illuminate\Queue\RedisQueue')->setMethods(['availableAt', 'getRandomId'])->setConstructorArgs([$redis = m::mock('Illuminate\Contracts\Redis\Factory'), 'default'])->getMock();
        $queue->expects($this->once())->method('getRandomId')->will($this->returnValue('foo'));
        $queue->expects($this->once())->method('availableAt')->with($date)->will($this->returnValue(2));

        $redis->shouldReceive('connection')->once()->andReturn($redis);
        $redis->shouldReceive('zadd')->once()->with(
            'queues:default:delayed',
            2,
            json_encode(['displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'timeout' => null, 'data' => ['data'], 'id' => 'foo', 'attempts' => 0])
        );

        $queue->later($date, 'foo', ['data']);
    }
}
