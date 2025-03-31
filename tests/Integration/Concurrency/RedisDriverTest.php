<?php

namespace Illuminate\Tests\Integration\Concurrency;

use Exception;
use Illuminate\Concurrency\RedisDriver;
use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Redis\RedisManager;
use Mockery as m;
use Orchestra\Testbench\TestCase;
use ReflectionFunction;
use stdClass;
use Laravel\SerializableClosure\SerializableClosure;

class RedisDriverTest extends TestCase
{
    use InteractsWithRedis;

    /**
     * The Redis manager instance.
     */
    protected $redisManager;

    /**
     * The queue prefix for Redis tasks.
     */
    protected string $queuePrefix = 'testing:concurrency:';

    /**
     * The mock processor for handling Redis tasks.
     */
    protected $mockProcessor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRedis();

        $this->redisManager = new RedisManager($this->app, 'phpredis', [
            'default' => [
                'host' => env('REDIS_HOST', '127.0.0.1'),
                'port' => env('REDIS_PORT', 6379),
                'database' => 5,
                'timeout' => 0.5,
            ],
        ]);
        
        // Clear any existing data in Redis
        $this->redisManager->connection('default')->flushdb();
        
        // Set up the mock processor - this mock will be manually triggered in each test
        $this->mockProcessor = new MockRedisProcessor($this->redisManager, 'default', $this->queuePrefix);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Clean up Redis keys
        $this->redisManager->connection('default')->flushdb();

        $this->tearDownRedis();
        m::close();
    }

    public function testRunMethodWithSingleTask()
    {
        if (! extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension is not loaded');
        }

        $driver = new RedisDriver($this->redisManager, 'default', $this->queuePrefix);

        // Arrange: Create a modified run method that processes the tasks after they're added to the queue
        $processRedisTasksAfterQueue = function () use ($driver) {
            // Add the task to Redis queue
            $result = $driver->run(function () {
                return 'Hello, World!';
            });
            
            // Simulate the processor running
            $this->mockProcessor->processQueuedTasks();
            
            return $result;
        };

        // Act & Assert
        $result = $processRedisTasksAfterQueue();
        $this->assertEquals(['Hello, World!'], $result);
    }

    public function testRunMethodWithMultipleTasks()
    {
        if (! extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension is not loaded');
        }

        $driver = new RedisDriver($this->redisManager, 'default', $this->queuePrefix);

        // Create custom array-based tasks
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

        // Monkey patch the run method to run the processor after queueing tasks
        $monkeyPatchedRun = function () use ($driver, $tasks) {
            // First step of run method - queue the tasks
            $reflection = new \ReflectionObject($driver);
            $runMethod = $reflection->getMethod('run');
            $runMethod->setAccessible(true);
            $runMethod->invokeArgs($driver, [$tasks]);
            
            // Now manually process the tasks
            $this->mockProcessor->processQueuedTasks();
            
            // Then get the results
            return $driver->run($tasks);
        };

        // Test with multiple tasks
        $result = $monkeyPatchedRun();

        $this->assertEquals(['Task 1', 'Task 2', 'Task 3'], $result);
    }

    public function testDeferMethod()
    {
        if (! extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension is not loaded');
        }

        $driver = new RedisDriver($this->redisManager, 'default', $this->queuePrefix);

        // Test with a single deferred task
        $deferred = $driver->defer(function () {
            return 'Deferred Task';
        });

        // Process the deferred tasks
        $this->mockProcessor->processQueuedTasks();

        $this->assertNotNull($deferred);
    }

    public function testErrorHandlingInRunMethod()
    {
        if (! extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension is not loaded');
        }

        $driver = new RedisDriver($this->redisManager, 'default', $this->queuePrefix);

        // Monkey patch the run method for testing error handling
        $monkeyPatchedRun = function () use ($driver) {
            // Create a task that throws an exception
            $task = function () {
                throw new Exception('Test exception');
            };
            
            // First step of run method - queue the task
            $reflection = new \ReflectionObject($driver);
            $runMethod = $reflection->getMethod('run');
            $runMethod->setAccessible(true);
            
            try {
                $runMethod->invokeArgs($driver, [[$task]]);
                // Process the task
                $this->mockProcessor->processQueuedTasks();
                // Let the driver fetch the result
                return $driver->run([$task]);
            } catch (Exception $e) {
                throw $e;
            }
        };

        // Test error handling
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test exception');

        $monkeyPatchedRun();
    }
}

/**
 * A mock processor for Redis tasks that simulates the behavior of RedisProcessorCommand.
 */
class MockRedisProcessor
{
    /**
     * Indicates if the processor is running.
     */
    protected bool $running = false;

    /**
     * Create a new mock processor instance.
     */
    public function __construct(
        protected RedisManager $redis,
        protected string $connection = 'default',
        protected string $queuePrefix = 'laravel:concurrency:'
    ) {
        //
    }

    /**
     * Start the mock processor.
     */
    public function start(): void
    {
        $this->running = true;
    }

    /**
     * Stop the mock processor.
     */
    public function stop(): void
    {
        $this->running = false;
    }

    /**
     * Process all tasks in the queue.
     */
    public function processQueuedTasks(): void
    {
        $redis = $this->redis->connection($this->connection);
        
        // Handle all current tasks
        $this->processPendingTasks($redis, $this->queuePrefix.'queue');
        
        // Handle all deferred tasks
        $this->processPendingTasks($redis, $this->queuePrefix.'deferred');
    }
    
    /**
     * Process all pending tasks from a specified queue.
     */
    protected function processPendingTasks(Connection $redis, string $queueName): void
    {
        while ($taskId = $redis->lpop($queueName)) {
            $this->processTask($redis, $taskId);
        }
    }

    /**
     * Process a single task.
     */
    protected function processTask(Connection $redis, string $taskId): void
    {
        // Get the task from Redis
        $serializedTask = $redis->get($taskId.':task');

        if (! $serializedTask) {
            return;
        }

        try {
            // Unserialize and execute the task
            $task = unserialize($serializedTask)->getClosure();
            $result = $task();

            // Store the result
            $redis->set(
                $taskId.':result',
                serialize(['result' => $result]),
                'EX',
                3600 // Expire in 1 hour
            );
        } catch (Exception $e) {
            // Store the error
            $redis->set(
                $taskId.':result',
                serialize(['error' => $e->getMessage()]),
                'EX',
                3600 // Expire in 1 hour
            );
        }
    }
}
