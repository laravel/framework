<?php

namespace Illuminate\Tests\Integration\Concurrency;

use Exception;
use Illuminate\Concurrency\RedisDriver;
use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Illuminate\Redis\RedisManager;
use Laravel\SerializableClosure\SerializableClosure;
use Mockery as m;
use Orchestra\Testbench\TestCase;

class RedisDriverTest extends TestCase
{
    use InteractsWithRedis;

    /**
     * The Redis manager instance.
     */
    protected $redisManager;

    /**
     * @var \Mockery\MockInterface
     */
    protected $mockRedisConnection;

    /**
     * The queue prefix for Redis tasks.
     */
    protected string $queuePrefix = 'testing:concurrency:';

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRedis();

        // Setup mock Redis connection to avoid actual Redis calls
        $this->redisManager = m::mock(RedisManager::class);
        $this->mockRedisConnection = m::mock(\Illuminate\Redis\Connections\Connection::class);
        
        // Set up common expectations for the Redis connection
        $this->redisManager->shouldReceive('connection')
            ->with('default')
            ->andReturn($this->mockRedisConnection);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        $this->tearDownRedis();
        m::close();
    }

    public function testRunMethodWithSingleTask()
    {
        // Create a test task
        $task = function () {
            return 'Hello, World!';
        };
        
        // Mock Redis calls
        $taskId = null;
        $this->mockRedisConnection->shouldReceive('set')
            ->with(m::pattern('/^testing:concurrency:.*:task$/'), m::type('string'), 'EX', 3600)
            ->andReturnUsing(function ($key, $value) use (&$taskId) {
                $taskId = str_replace(':task', '', $key);
                return true;
            });
            
        $this->mockRedisConnection->shouldReceive('rpush')
            ->with($this->queuePrefix.'queue', m::pattern('/^testing:concurrency:.*$/'))
            ->andReturn(1);
        
        $this->mockRedisConnection->shouldReceive('get')
            ->with(m::pattern('/^testing:concurrency:.*:result$/'))
            ->andReturnUsing(function ($key) use (&$taskId) {
                // Only return a result if the key matches our task ID
                if (str_replace(':result', '', $key) === $taskId) {
                    return serialize(['result' => 'Hello, World!']);
                }
                return null;
            });
            
        $this->mockRedisConnection->shouldReceive('del')
            ->with(m::pattern('/^testing:concurrency:.*:task$/'), m::pattern('/^testing:concurrency:.*:result$/'))
            ->andReturn(2);

        // Create and test the driver
        $driver = new RedisDriver($this->redisManager, 'default', $this->queuePrefix);
        $result = $driver->run($task);

        // Assert the result is as expected
        $this->assertEquals(['Hello, World!'], $result);
    }

    public function testRunMethodWithMultipleTasks()
    {
        // Create test tasks
        $tasks = [
            function () {
                return 'Task 1';
            },
            function () {
                return 'Task 2';
            },
            function () {
                return 'Task 3';
            },
        ];
        
        // Store task IDs
        $taskIds = [];
        
        // Mock Redis calls
        $this->mockRedisConnection->shouldReceive('set')
            ->with(m::pattern('/^testing:concurrency:.*:task$/'), m::type('string'), 'EX', 3600)
            ->andReturnUsing(function ($key, $value) use (&$taskIds) {
                $taskId = str_replace(':task', '', $key);
                $taskIds[] = $taskId;
                return true;
            })
            ->times(3);
            
        $this->mockRedisConnection->shouldReceive('rpush')
            ->with($this->queuePrefix.'queue', m::pattern('/^testing:concurrency:.*$/'))
            ->andReturn(1)
            ->times(3);
        
        $resultMap = [
            'Task 1',
            'Task 2',
            'Task 3',
        ];
        
        $this->mockRedisConnection->shouldReceive('get')
            ->with(m::pattern('/^testing:concurrency:.*:result$/'))
            ->andReturnUsing(function ($key) use (&$taskIds, $resultMap) {
                // Find which task this is
                $taskKey = str_replace(':result', '', $key);
                $index = array_search($taskKey, $taskIds);
                
                if ($index !== false) {
                    return serialize(['result' => $resultMap[$index]]);
                }
                
                return null;
            });
            
        $this->mockRedisConnection->shouldReceive('del')
            ->with(m::pattern('/^testing:concurrency:.*:task$/'), m::pattern('/^testing:concurrency:.*:result$/'))
            ->andReturn(2)
            ->times(3);

        // Create and test the driver
        $driver = new RedisDriver($this->redisManager, 'default', $this->queuePrefix);
        $result = $driver->run($tasks);

        // Assert the result is as expected
        $this->assertEquals(['Task 1', 'Task 2', 'Task 3'], $result);
    }

    public function testDeferMethod()
    {
        // Mock Redis calls
        $this->mockRedisConnection->shouldReceive('set')
            ->with(m::pattern('/^testing:concurrency:.*:task$/'), m::type('string'), 'EX', 3600)
            ->andReturn(true);
            
        $this->mockRedisConnection->shouldReceive('rpush')
            ->with($this->queuePrefix.'deferred', m::pattern('/^testing:concurrency:.*$/'))
            ->andReturn(1);

        // Create and test the driver
        $driver = new RedisDriver($this->redisManager, 'default', $this->queuePrefix);
        $deferred = $driver->defer(function () {
            return 'Deferred Task';
        });

        // Assert we got a deferred callback
        $this->assertNotNull($deferred);
    }

    public function testErrorHandlingInRunMethod()
    {
        // Create a test task
        $task = function () {
            throw new Exception('Test exception');
        };
        
        // Mock Redis calls
        $taskId = null;
        $this->mockRedisConnection->shouldReceive('set')
            ->with(m::pattern('/^testing:concurrency:.*:task$/'), m::type('string'), 'EX', 3600)
            ->andReturnUsing(function ($key, $value) use (&$taskId) {
                $taskId = str_replace(':task', '', $key);
                return true;
            });
            
        $this->mockRedisConnection->shouldReceive('rpush')
            ->with($this->queuePrefix.'queue', m::pattern('/^testing:concurrency:.*$/'))
            ->andReturn(1);
        
        $this->mockRedisConnection->shouldReceive('get')
            ->with(m::pattern('/^testing:concurrency:.*:result$/'))
            ->andReturnUsing(function ($key) use (&$taskId) {
                // Only return an error if the key matches our task ID
                if (str_replace(':result', '', $key) === $taskId) {
                    return serialize(['error' => 'Test exception']);
                }
                return null;
            });

        // Create and test the driver
        $driver = new RedisDriver($this->redisManager, 'default', $this->queuePrefix);
        
        // Test for exception
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test exception');
        
        $driver->run($task);
    }
}
