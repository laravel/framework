<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Container\Container;
use Illuminate\Contracts\Redis\Factory;
use Illuminate\Queue\LuaScripts;
use Illuminate\Queue\Queue;
use Illuminate\Queue\RedisQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class QueueRedisQueueTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testPushProperlyPushesJobOntoRedis()
    {
        $uuid = Str::uuid();

        Str::createUuidsUsing(function () use ($uuid) {
            return $uuid;
        });

        $time = Carbon::now();
        Carbon::setTestNow($time);

        $queue = $this->getMockBuilder(RedisQueue::class)->onlyMethods(['getRandomId'])->setConstructorArgs([$redis = m::mock(Factory::class), 'default'])->getMock();
        $queue->expects($this->once())->method('getRandomId')->willReturn('foo');
        $queue->setContainer($container = m::spy(Container::class));
        $redis->shouldReceive('connection')->once()->andReturn($redis);
        $redis->shouldReceive('eval')->once()->with(LuaScripts::push(), 2, 'queues:default', 'queues:default:notify', json_encode(['uuid' => $uuid, 'displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'maxExceptions' => null, 'failOnTimeout' => false, 'backoff' => null, 'timeout' => null, 'data' => ['data'], 'createdAt' => $time->getTimestamp(), 'id' => 'foo', 'attempts' => 0, 'delay' => null]));

        $id = $queue->push('foo', ['data']);
        $this->assertSame('foo', $id);
        $container->shouldHaveReceived('bound')->with('events')->twice();

        Carbon::setTestNow();
        Str::createUuidsNormally();
    }

    public function testPushProperlyPushesJobOntoRedisWithCustomPayloadHook()
    {
        $uuid = Str::uuid();

        Str::createUuidsUsing(function () use ($uuid) {
            return $uuid;
        });

        $time = Carbon::now();
        Carbon::setTestNow($time);

        $queue = $this->getMockBuilder(RedisQueue::class)->onlyMethods(['getRandomId'])->setConstructorArgs([$redis = m::mock(Factory::class), 'default'])->getMock();
        $queue->expects($this->once())->method('getRandomId')->willReturn('foo');
        $queue->setContainer($container = m::spy(Container::class));
        $redis->shouldReceive('connection')->once()->andReturn($redis);
        $redis->shouldReceive('eval')->once()->with(LuaScripts::push(), 2, 'queues:default', 'queues:default:notify', json_encode(['uuid' => $uuid, 'displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'maxExceptions' => null, 'failOnTimeout' => false, 'backoff' => null, 'timeout' => null, 'data' => ['data'], 'createdAt' => $time->getTimestamp(), 'custom' => 'taylor', 'id' => 'foo', 'attempts' => 0, 'delay' => null]));

        Queue::createPayloadUsing(function ($connection, $queue, $payload) {
            return ['custom' => 'taylor'];
        });

        $id = $queue->push('foo', ['data']);
        $this->assertSame('foo', $id);
        $container->shouldHaveReceived('bound')->with('events')->twice();

        Queue::createPayloadUsing(null);

        Carbon::setTestNow();
        Str::createUuidsNormally();
    }

    public function testPushProperlyPushesJobOntoRedisWithTwoCustomPayloadHook()
    {
        $uuid = Str::uuid();

        Str::createUuidsUsing(function () use ($uuid) {
            return $uuid;
        });

        $time = Carbon::now();
        Carbon::setTestNow($time);

        $queue = $this->getMockBuilder(RedisQueue::class)->onlyMethods(['getRandomId'])->setConstructorArgs([$redis = m::mock(Factory::class), 'default'])->getMock();
        $queue->expects($this->once())->method('getRandomId')->willReturn('foo');
        $queue->setContainer($container = m::spy(Container::class));
        $redis->shouldReceive('connection')->once()->andReturn($redis);
        $redis->shouldReceive('eval')->once()->with(LuaScripts::push(), 2, 'queues:default', 'queues:default:notify', json_encode(['uuid' => $uuid, 'displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'maxExceptions' => null, 'failOnTimeout' => false, 'backoff' => null, 'timeout' => null, 'data' => ['data'], 'createdAt' => $time->getTimestamp(), 'custom' => 'taylor', 'bar' => 'foo', 'id' => 'foo', 'attempts' => 0, 'delay' => null]));

        Queue::createPayloadUsing(function ($connection, $queue, $payload) {
            return ['custom' => 'taylor'];
        });

        Queue::createPayloadUsing(function ($connection, $queue, $payload) {
            return ['bar' => 'foo'];
        });

        $id = $queue->push('foo', ['data']);
        $this->assertSame('foo', $id);
        $container->shouldHaveReceived('bound')->with('events')->twice();

        Queue::createPayloadUsing(null);

        Carbon::setTestNow();
        Str::createUuidsNormally();
    }

    public function testDelayedPushProperlyPushesJobOntoRedis()
    {
        $uuid = Str::uuid();

        Str::createUuidsUsing(function () use ($uuid) {
            return $uuid;
        });

        $time = Carbon::now();
        Carbon::setTestNow($time);

        $queue = $this->getMockBuilder(RedisQueue::class)->onlyMethods(['availableAt', 'getRandomId'])->setConstructorArgs([$redis = m::mock(Factory::class), 'default'])->getMock();
        $queue->setContainer($container = m::spy(Container::class));
        $queue->expects($this->once())->method('getRandomId')->willReturn('foo');
        $queue->expects($this->once())->method('availableAt')->with(1)->willReturn(2);

        $redis->shouldReceive('connection')->once()->andReturn($redis);
        $redis->shouldReceive('zadd')->once()->with(
            'queues:default:delayed',
            2,
            json_encode(['uuid' => $uuid, 'displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'maxExceptions' => null, 'failOnTimeout' => false, 'backoff' => null, 'timeout' => null, 'data' => ['data'], 'createdAt' => $time->getTimestamp(), 'id' => 'foo', 'attempts' => 0, 'delay' => 1])
        );

        $id = $queue->later(1, 'foo', ['data']);
        $this->assertSame('foo', $id);
        $container->shouldHaveReceived('bound')->with('events')->twice();

        Carbon::setTestNow();
        Str::createUuidsNormally();
    }

    public function testDelayedPushWithDateTimeProperlyPushesJobOntoRedis()
    {
        $uuid = Str::uuid();

        Str::createUuidsUsing(function () use ($uuid) {
            return $uuid;
        });

        $time = $date = Carbon::now();
        Carbon::setTestNow($time);
        $queue = $this->getMockBuilder(RedisQueue::class)->onlyMethods(['availableAt', 'getRandomId'])->setConstructorArgs([$redis = m::mock(Factory::class), 'default'])->getMock();
        $queue->setContainer($container = m::spy(Container::class));
        $queue->expects($this->once())->method('getRandomId')->willReturn('foo');
        $queue->expects($this->once())->method('availableAt')->with($date)->willReturn(5);

        $redis->shouldReceive('connection')->once()->andReturn($redis);
        $redis->shouldReceive('zadd')->once()->with(
            'queues:default:delayed',
            5,
            json_encode(['uuid' => $uuid, 'displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'maxExceptions' => null, 'failOnTimeout' => false, 'backoff' => null, 'timeout' => null, 'data' => ['data'], 'createdAt' => $time->getTimestamp(), 'id' => 'foo', 'attempts' => 0, 'delay' => 5])
        );

        $queue->later($date->addSeconds(5), 'foo', ['data']);
        $container->shouldHaveReceived('bound')->with('events')->twice();

        Carbon::setTestNow();
        Str::createUuidsNormally();
    }
}
