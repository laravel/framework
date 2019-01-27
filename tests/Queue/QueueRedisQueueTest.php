<?php

namespace Illuminate\Tests\Queue;

use Mockery as m;
use Illuminate\Queue\Queue;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Illuminate\Queue\LuaScripts;
use Illuminate\Queue\RedisQueue;
use Illuminate\Contracts\Redis\Factory;

class QueueRedisQueueTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testPushProperlyPushesJobOntoRedis()
    {
        $queue = $this->getMockBuilder(RedisQueue::class)->setMethods(['getRandomId'])->setConstructorArgs([$redis = m::mock(Factory::class), 'default'])->getMock();
        $queue->expects($this->once())->method('getRandomId')->will($this->returnValue('foo'));
        $redis->shouldReceive('connection')->once()->andReturn($redis);
        $redis->shouldReceive('eval')->once()->with(LuaScripts::push(), 2, 'queues:default', 'queues:default:notify', json_encode(['displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'timeout' => null, 'data' => ['data'], 'id' => 'foo', 'attempts' => 0]));

        $id = $queue->push('foo', ['data']);
        $this->assertEquals('foo', $id);
    }

    public function testPushProperlyPushesJobOntoRedisWithCustomPayloadHook()
    {
        $queue = $this->getMockBuilder(RedisQueue::class)->setMethods(['getRandomId'])->setConstructorArgs([$redis = m::mock(Factory::class), 'default'])->getMock();
        $queue->expects($this->once())->method('getRandomId')->will($this->returnValue('foo'));
        $redis->shouldReceive('connection')->once()->andReturn($redis);
        $redis->shouldReceive('eval')->once()->with(LuaScripts::push(), 2, 'queues:default', 'queues:default:notify', json_encode(['displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'timeout' => null, 'data' => ['data'], 'custom' => 'taylor', 'id' => 'foo', 'attempts' => 0]));

        Queue::createPayloadUsing(function ($connection, $queue, $payload) {
            return ['custom' => 'taylor'];
        });

        $id = $queue->push('foo', ['data']);
        $this->assertEquals('foo', $id);

        Queue::createPayloadUsing(null);
    }

    public function testPushProperlyPushesJobOntoRedisWithTwoCustomPayloadHook()
    {
        $queue = $this->getMockBuilder(RedisQueue::class)->setMethods(['getRandomId'])->setConstructorArgs([$redis = m::mock(Factory::class), 'default'])->getMock();
        $queue->expects($this->once())->method('getRandomId')->will($this->returnValue('foo'));
        $redis->shouldReceive('connection')->once()->andReturn($redis);
        $redis->shouldReceive('eval')->once()->with(LuaScripts::push(), 2, 'queues:default', 'queues:default:notify', json_encode(['displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'timeout' => null, 'data' => ['data'], 'custom' => 'taylor', 'bar' => 'foo', 'id' => 'foo', 'attempts' => 0]));

        Queue::createPayloadUsing(function ($connection, $queue, $payload) {
            return ['custom' => 'taylor'];
        });

        Queue::createPayloadUsing(function ($connection, $queue, $payload) {
            return ['bar' => 'foo'];
        });

        $id = $queue->push('foo', ['data']);
        $this->assertEquals('foo', $id);

        Queue::createPayloadUsing(null);
    }

    public function testDelayedPushProperlyPushesJobOntoRedis()
    {
        $queue = $this->getMockBuilder(RedisQueue::class)->setMethods(['availableAt', 'getRandomId'])->setConstructorArgs([$redis = m::mock(Factory::class), 'default'])->getMock();
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
        $date = Carbon::now();
        $queue = $this->getMockBuilder(RedisQueue::class)->setMethods(['availableAt', 'getRandomId'])->setConstructorArgs([$redis = m::mock(Factory::class), 'default'])->getMock();
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
