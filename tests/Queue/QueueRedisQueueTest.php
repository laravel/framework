<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Container\Container;
use Illuminate\Contracts\Redis\Factory;
use Illuminate\Queue\LuaScripts;
use Illuminate\Queue\Queue;
use Illuminate\Queue\RedisQueue;
use Illuminate\Redis\Connections\PhpRedisClusterConnection;
use Illuminate\Redis\Connections\PredisClusterConnection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class QueueRedisQueueTest extends TestCase
{
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
        $redis->shouldReceive('connection')->atLeast()->once()->andReturn($redis);
        $redis->shouldReceive('isClusterAware')->andReturn(false);
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
        $redis->shouldReceive('connection')->atLeast()->once()->andReturn($redis);
        $redis->shouldReceive('isClusterAware')->andReturn(false);
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
        $redis->shouldReceive('connection')->atLeast()->once()->andReturn($redis);
        $redis->shouldReceive('isClusterAware')->andReturn(false);
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

        $redis->shouldReceive('connection')->atLeast()->once()->andReturn($redis);
        $redis->shouldReceive('isClusterAware')->andReturn(false);
        $redis->shouldReceive('eval')->once()->with(
            LuaScripts::later(),
            1,
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

        $redis->shouldReceive('connection')->atLeast()->once()->andReturn($redis);
        $redis->shouldReceive('isClusterAware')->andReturn(false);
        $redis->shouldReceive('eval')->once()->with(
            LuaScripts::later(),
            1,
            'queues:default:delayed',
            5,
            json_encode(['uuid' => $uuid, 'displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'maxExceptions' => null, 'failOnTimeout' => false, 'backoff' => null, 'timeout' => null, 'data' => ['data'], 'createdAt' => $time->getTimestamp(), 'id' => 'foo', 'attempts' => 0, 'delay' => 5])
        );

        $queue->later($date->addSeconds(5), 'foo', ['data']);
        $container->shouldHaveReceived('bound')->with('events')->twice();

        Carbon::setTestNow();
        Str::createUuidsNormally();
    }

    public function testGetQueueRemainsUnchangedForNonCluster()
    {
        $queue = new RedisQueue($redis = m::mock(Factory::class), 'default');
        $this->assertSame('queues:default', $queue->getQueue(null));
        $this->assertSame('queues:emails', $queue->getQueue('emails'));
    }

    public function testGetQueueRemainsUnchangedForCluster()
    {
        $queue = new RedisQueue($redis = m::mock(Factory::class), 'default');
        $redis->shouldReceive('connection')->andReturn(m::mock(PhpRedisClusterConnection::class));

        // getQueue() should NOT add hash tags — it's unchanged
        $this->assertSame('queues:default', $queue->getQueue(null));
        $this->assertSame('queues:emails', $queue->getQueue('emails'));
    }

    public function testGetRedisKeyReturnsPlainKeyForNonCluster()
    {
        $queue = new TestableRedisQueue($redis = m::mock(Factory::class), 'default');
        $connection = m::mock(\Illuminate\Redis\Connections\Connection::class);
        $connection->shouldReceive('isClusterAware')->andReturn(false);
        $redis->shouldReceive('connection')->andReturn($connection);

        $this->assertSame('queues:default', $queue->testGetQueueRedisKey(null));
        $this->assertSame('queues:emails', $queue->testGetQueueRedisKey('emails'));
    }

    public function testGetRedisKeyWrapsWithHashTagsForPhpRedisCluster()
    {
        $queue = new TestableRedisQueue($redis = m::mock(Factory::class), 'default');
        $connection = m::mock(PhpRedisClusterConnection::class);
        $connection->shouldReceive('isClusterAware')->andReturn(true);
        $redis->shouldReceive('connection')->andReturn($connection);

        $this->assertSame('queues:{default}', $queue->testGetQueueRedisKey(null));
        $this->assertSame('queues:{emails}', $queue->testGetQueueRedisKey('emails'));
    }

    public function testGetRedisKeyWrapsWithHashTagsForPredisCluster()
    {
        $queue = new TestableRedisQueue($redis = m::mock(Factory::class), 'default');
        $connection = m::mock(PredisClusterConnection::class);
        $connection->shouldReceive('isClusterAware')->andReturn(true);
        $redis->shouldReceive('connection')->andReturn($connection);

        $this->assertSame('queues:{default}', $queue->testGetQueueRedisKey(null));
        $this->assertSame('queues:{emails}', $queue->testGetQueueRedisKey('emails'));
    }

    public function testGetRedisKeyDoesNotDoubleWrapExistingHashTags()
    {
        $queue = new TestableRedisQueue($redis = m::mock(Factory::class), '{default}');
        $connection = m::mock(PhpRedisClusterConnection::class);
        $connection->shouldReceive('isClusterAware')->andReturn(true);
        $redis->shouldReceive('connection')->andReturn($connection);

        $this->assertSame('queues:{default}', $queue->testGetQueueRedisKey(null));
        $this->assertSame('queues:{custom}', $queue->testGetQueueRedisKey('{custom}'));
    }

    public function testGetRedisKeySkipsWrappingWhenQueueNameContainsBraces()
    {
        $queue = new TestableRedisQueue($redis = m::mock(Factory::class), 'default');
        $connection = m::mock(PhpRedisClusterConnection::class);
        $connection->shouldReceive('isClusterAware')->andReturn(true);
        $redis->shouldReceive('connection')->andReturn($connection);

        // Queue name already contains hash tags — skip wrapping
        $this->assertSame('queues:process-{batch}-results', $queue->testGetQueueRedisKey('process-{batch}-results'));
    }

    public function testGetRedisKeyWrapsEmptyHashTagOnCluster()
    {
        $queue = new TestableRedisQueue($redis = m::mock(Factory::class), 'default');
        $connection = m::mock(PhpRedisClusterConnection::class);
        $connection->shouldReceive('isClusterAware')->andReturn(true);
        $redis->shouldReceive('connection')->andReturn($connection);

        // Empty braces '{}' are not a valid hash tag — should still get wrapped
        $this->assertSame('queues:{my{}queue}', $queue->testGetQueueRedisKey('my{}queue'));
    }

    public function testGetRedisKeyWrapsUnmatchedOpeningBrace()
    {
        $queue = new TestableRedisQueue($redis = m::mock(Factory::class), 'default');
        $connection = m::mock(PhpRedisClusterConnection::class);
        $connection->shouldReceive('isClusterAware')->andReturn(true);
        $redis->shouldReceive('connection')->andReturn($connection);

        // Unmatched '{' is not a valid hash tag — should still get wrapped
        $this->assertSame('queues:{my{broken}', $queue->testGetQueueRedisKey('my{broken'));
    }

    public function testGetRedisKeyWrapsUnmatchedClosingBrace()
    {
        $queue = new TestableRedisQueue($redis = m::mock(Factory::class), 'default');
        $connection = m::mock(PhpRedisClusterConnection::class);
        $connection->shouldReceive('isClusterAware')->andReturn(true);
        $redis->shouldReceive('connection')->andReturn($connection);

        // Unmatched '}' is not a valid hash tag — should still get wrapped
        $this->assertSame('queues:{broken}queue}', $queue->testGetQueueRedisKey('broken}queue'));
    }

    public function testGetRedisKeyWrapsEmptyFirstHashTagFollowedByValidPair()
    {
        $queue = new TestableRedisQueue($redis = m::mock(Factory::class), 'default');
        $connection = m::mock(PhpRedisClusterConnection::class);
        $connection->shouldReceive('isClusterAware')->andReturn(true);
        $redis->shouldReceive('connection')->andReturn($connection);

        // Redis spec: the first '{}' is an empty hash tag, so the whole key is hashed
        // even though '{bar}' looks valid. Must be wrapped to ensure slot affinity.
        $this->assertSame('queues:{foo{}{bar}}', $queue->testGetQueueRedisKey('foo{}{bar}'));
    }

    public function testPushUsesGetRedisKeyForLuaScript()
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

        $clusterConnection = m::mock(PhpRedisClusterConnection::class)->shouldIgnoreMissing();
        $clusterConnection->shouldReceive('isClusterAware')->andReturn(true);
        $redis->shouldReceive('connection')->andReturn($clusterConnection);

        // command() is called by eval() — assert it receives hash-tagged keys
        $clusterConnection->shouldReceive('command')->once()->with('eval', m::on(function ($args) {
            return $args[0] === LuaScripts::push()
                && $args[2] === 2
                && $args[1][0] === 'queues:{default}'
                && $args[1][1] === 'queues:{default}:notify';
        }))->andReturn(null);

        $queue->push('foo', ['data']);

        Carbon::setTestNow();
        Str::createUuidsNormally();
    }

    public function testPushPassesUnchangedQueueToCreatePayload()
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

        $clusterConnection = m::mock(PhpRedisClusterConnection::class)->shouldIgnoreMissing();
        $clusterConnection->shouldReceive('isClusterAware')->andReturn(true);
        $redis->shouldReceive('connection')->andReturn($clusterConnection);

        $receivedQueue = null;
        Queue::createPayloadUsing(function ($connection, $queue) use (&$receivedQueue) {
            $receivedQueue = $queue;

            return [];
        });

        $queue->push('foo', ['data']);

        // Payload hook should receive the unchanged getQueue() output (no hash tags)
        $this->assertSame('queues:default', $receivedQueue);

        Queue::createPayloadUsing(null);
        Carbon::setTestNow();
        Str::createUuidsNormally();
    }

    public function testSizeUsesGetRedisKeyOnCluster()
    {
        $queue = new RedisQueue($redis = m::mock(Factory::class), 'default');
        $clusterConnection = m::mock(PhpRedisClusterConnection::class)->shouldIgnoreMissing();
        $clusterConnection->shouldReceive('isClusterAware')->andReturn(true);
        $redis->shouldReceive('connection')->andReturn($clusterConnection);

        $clusterConnection->shouldReceive('command')->once()->with('eval', m::on(function ($args) {
            return $args[0] === LuaScripts::size()
                && $args[2] === 3
                && $args[1][0] === 'queues:{default}'
                && $args[1][1] === 'queues:{default}:delayed'
                && $args[1][2] === 'queues:{default}:reserved';
        }))->andReturn(5);

        $this->assertSame(5, $queue->size());
    }

    public function testClearUsesGetRedisKeyOnCluster()
    {
        $queue = new RedisQueue($redis = m::mock(Factory::class), 'default');
        $clusterConnection = m::mock(PhpRedisClusterConnection::class)->shouldIgnoreMissing();
        $clusterConnection->shouldReceive('isClusterAware')->andReturn(true);
        $redis->shouldReceive('connection')->andReturn($clusterConnection);

        $clusterConnection->shouldReceive('command')->once()->with('eval', m::on(function ($args) {
            return $args[0] === LuaScripts::clear()
                && $args[2] === 4
                && $args[1][0] === 'queues:{default}'
                && $args[1][1] === 'queues:{default}:delayed'
                && $args[1][2] === 'queues:{default}:reserved'
                && $args[1][3] === 'queues:{default}:notify';
        }))->andReturn(3);

        $this->assertSame(3, $queue->clear('default'));
    }

    public function testIsClusterAwareConnectionCachesResult()
    {
        $queue = new TestableRedisQueue($redis = m::mock(Factory::class), 'default');
        $connection = m::mock(PhpRedisClusterConnection::class);
        $connection->shouldReceive('isClusterAware')->once()->andReturn(true);
        $redis->shouldReceive('connection')->once()->andReturn($connection);

        // Multiple calls should only trigger one connection() call
        $this->assertTrue($queue->testIsClusterAwareConnection());
        $this->assertTrue($queue->testIsClusterAwareConnection());
        $this->assertTrue($queue->testIsClusterAwareConnection());
    }
}

class TestableRedisQueue extends RedisQueue
{
    public function testGetQueueRedisKey($queue = null)
    {
        return $this->getQueueRedisKey($queue);
    }

    public function testIsClusterAwareConnection()
    {
        return $this->isClusterAwareConnection();
    }
}
