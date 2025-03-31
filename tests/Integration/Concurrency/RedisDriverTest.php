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
        
        // Set up the mock processor
        $this->mockProcessor = new MockRedisProcessor($this->redisManager, 'default', $this->queuePrefix);
        $this->mockProcessor->start();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Stop the mock processor
        if ($this->mockProcessor) {
            $this->mockProcessor->stop();
        }
        
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

        // Test with a single task
        $result = $driver->run(function () {
            return 'Hello, World!';
        });

        $this->assertEquals(['Hello, World!'], $result);
    }

    public function testRunMethodWithMultipleTasks()
    {
        if (! extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension is not loaded');
        }

        $driver = new RedisDriver($this->redisManager, 'default', $this->queuePrefix);

        // Test with multiple tasks
        $result = $driver->run([
            function () {
                return 'Task 1';
            },
            function () {
                return 'Task 2';
            },
            function () {
                return 'Task 3';
            },
        ]);

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

        $this->assertNotNull($deferred);
    }

    public function testErrorHandlingInRunMethod()
    {
        if (! extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension is not loaded');
        }

        $driver = new RedisDriver($this->redisManager, 'default', $this->queuePrefix);

        // Test error handling
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test exception');

        $driver->run(function () {
            throw new Exception('Test exception');
        });
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
        $this->processQueuedTasks();
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
    protected function processQueuedTasks(): void
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
