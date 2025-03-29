<?php

namespace Illuminate\Concurrency;

use Closure;
use Exception;
use Illuminate\Contracts\Concurrency\Driver;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Defer\DeferredCallback;
use Illuminate\Support\Str;
use Laravel\SerializableClosure\SerializableClosure;
use Throwable;

use function Illuminate\Support\defer;

class RedisDriver implements Driver
{
    /**
     * Create a new Redis based concurrency driver.
     */
    public function __construct(
        protected RedisFactory $redis,
        protected string $connection = 'default',
        protected string $queuePrefix = 'laravel:concurrency:'
    ) {
        //
    }

    /**
     * Run the given tasks concurrently and return an array containing the results.
     */
    public function run(Closure|array $tasks): array
    {
        $tasks = Arr::wrap($tasks);
        $taskIds = [];
        $results = [];
        $client = $this->redis->connection($this->connection);

        // Generate unique IDs for each task
        foreach ($tasks as $key => $task) {
            $taskId = $this->queuePrefix.Str::uuid()->toString();
            $taskIds[$key] = $taskId;
            
            // Serialize and queue the task
            $client->set(
                $taskId.':task',
                serialize(new SerializableClosure($task)),
                'EX',
                3600 // Expire in 1 hour
            );
            
            // Add to processing queue
            $client->rpush($this->queuePrefix.'queue', $taskId);
        }

        // Execute tasks and collect results
        $startTime = microtime(true);
        $timeout = 60; // Timeout in seconds
        
        // Wait for results
        while (count($results) < count($tasks) && (microtime(true) - $startTime) < $timeout) {
            foreach ($taskIds as $key => $taskId) {
                if (isset($results[$key])) {
                    continue;
                }
                
                // Check if result is available
                $resultData = $client->get($taskId.':result');
                
                if ($resultData) {
                    $resultArray = unserialize($resultData);
                    
                    // If task has error, throw exception
                    if (isset($resultArray['error'])) {
                        throw new Exception($resultArray['error']);
                    }
                    
                    $results[$key] = $resultArray['result'];
                    
                    // Clean up
                    $client->del($taskId.':task', $taskId.':result');
                }
            }
            
            // Small delay to prevent CPU spinning
            usleep(50000); // 50ms
        }
        
        // Check for timeout
        if (count($results) < count($tasks)) {
            throw new Exception('Timed out while waiting for concurrent tasks to complete');
        }
        
        return $results;
    }

    /**
     * Start the given tasks in the background after the current task has finished.
     */
    public function defer(Closure|array $tasks): DeferredCallback
    {
        $client = $this->redis->connection($this->connection);
        $tasks = Arr::wrap($tasks);
        
        return defer(function () use ($tasks, $client) {
            foreach ($tasks as $task) {
                $taskId = $this->queuePrefix.Str::uuid()->toString();
                
                // Serialize and queue the task
                $client->set(
                    $taskId.':task',
                    serialize(new SerializableClosure($task)),
                    'EX',
                    3600 // Expire in 1 hour
                );
                
                // Add to deferred queue
                $client->rpush($this->queuePrefix.'deferred', $taskId);
            }
        });
    }

    /**
     * Get the Redis connection to be used.
     *
     * @return \Illuminate\Redis\Connections\Connection
     */
    protected function getRedisConnection()
    {
        return $this->redis->connection($this->connection);
    }
} 