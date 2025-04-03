<?php

namespace Illuminate\Concurrency\Console;

use Exception;
use Illuminate\Concurrency\RedisDriver;
use Illuminate\Console\Command;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Support\InteractsWithTime;
use Throwable;

class RedisProcessorCommand extends Command
{
    use InteractsWithTime;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'concurrency:redis-processor 
                            {--connection= : The Redis connection to use}
                            {--queue-prefix= : The queue prefix to use}
                            {--timeout= : The number of seconds to run the processor}
                            {--sleep= : The number of seconds to sleep when no jobs are found}
                            {--max-attempts= : The maximum number of times to attempt reconnection}
                            {--scheduled-check-interval= : How often to check for scheduled tasks in seconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process concurrent tasks from Redis queue';

    /**
     * Indicates if the command should be stopped.
     *
     * @var bool
     */
    protected $shouldQuit = false;

    /**
     * The Redis factory implementation.
     *
     * @var \Illuminate\Contracts\Redis\Factory
     */
    protected $redis;

    /**
     * The connection name.
     *
     * @var string
     */
    protected $connection;

    /**
     * The Redis connection.
     *
     * @var \Illuminate\Redis\Connections\Connection
     */
    protected $redisConnection;

    /**
     * The queue prefix.
     *
     * @var string
     */
    protected $queuePrefix;

    /**
     * The Redis driver instance.
     *
     * @var \Illuminate\Concurrency\RedisDriver
     */
    protected $redisDriver;

    /**
     * When to next check scheduled tasks.
     *
     * @var int
     */
    protected $nextScheduledCheck;

    /**
     * Execute the console command.
     *
     * @param  \Illuminate\Contracts\Redis\Factory  $redis
     * @return int
     */
    public function handle(RedisFactory $redis)
    {
        $this->redis = $redis;

        // Get configuration from config file
        $config = $this->laravel['config']['concurrency.driver.redis'] ?? [];
        $processorConfig = $config['processor'] ?? [];

        // Get command options or fall back to config values or defaults
        $this->connection = $this->option('connection') ?: ($config['connection'] ?? 'default');
        $this->queuePrefix = $this->option('queue-prefix') ?: ($config['queue_prefix'] ?? 'laravel:concurrency:');
        $timeout = (int) ($this->option('timeout') ?: ($processorConfig['timeout'] ?? 0));
        $sleep = (int) ($this->option('sleep') ?: ($processorConfig['sleep'] ?? 1));
        $maxAttempts = (int) ($this->option('max-attempts') ?: ($processorConfig['max_attempts'] ?? 3));
        $scheduledCheckInterval = (int) ($this->option('scheduled-check-interval') ?: ($processorConfig['scheduled_check_interval'] ?? 1));
        $lockTimeout = (int) ($config['lock_timeout'] ?? 60);

        // Initialize scheduled check time
        $this->nextScheduledCheck = $this->currentTime();

        // Create Redis driver instance for lock handling
        $this->redisDriver = new RedisDriver(
            $redis,
            $this->connection,
            $this->queuePrefix,
            $lockTimeout
        );

        $this->info("Processing Redis concurrency tasks on [{$this->connection}]");
        $this->info("Queue prefix: {$this->queuePrefix}");

        if ($timeout > 0) {
            $this->info("Processor will run for {$timeout} seconds");
        } else {
            $this->info('Processor will run until manually stopped');
        }

        // Setup signal handlers for graceful shutdown
        if (extension_loaded('pcntl')) {
            $this->handleSignals();
            $this->info('Signal handling enabled (SIGTERM, SIGINT, SIGUSR2)');
        } else {
            $this->warn('PCNTL extension not loaded, signal handling is disabled');
        }

        try {
            $this->redisConnection = $this->getRedisConnection();
        } catch (Throwable $e) {
            $this->error('Failed to connect to Redis: '.$e->getMessage());

            return Command::FAILURE;
        }

        $shouldRun = true;
        $startTime = $this->currentTime();

        while ($shouldRun && ! $this->shouldQuit) {
            try {
                // First, check if we need to process scheduled tasks
                if ($this->currentTime() >= $this->nextScheduledCheck) {
                    $this->processScheduledTasks($maxAttempts);
                    $this->nextScheduledCheck = $this->currentTime() + $scheduledCheckInterval;
                }

                // Process tasks from the queue
                $taskId = $this->withRedisRetry(function () {
                    return $this->redisConnection->lpop($this->queuePrefix.'queue');
                }, $maxAttempts);

                if ($taskId) {
                    $this->processTask($taskId, $maxAttempts);
                } else {
                    // Process deferred tasks if there are no immediate tasks
                    $deferredTaskId = $this->withRedisRetry(function () {
                        return $this->redisConnection->lpop($this->queuePrefix.'deferred');
                    }, $maxAttempts);

                    if ($deferredTaskId) {
                        $this->processTask($deferredTaskId, $maxAttempts);
                    } else {
                        // No tasks found, sleep for a while
                        $this->sleep($sleep);
                    }
                }

                // Check if we should exit based on timeout
                if ($timeout > 0 && $this->currentTime() - $startTime >= $timeout) {
                    $shouldRun = false;
                    $this->info('Timeout reached, stopping processor');
                }
            } catch (Throwable $e) {
                $this->error('Redis error: '.$e->getMessage());

                // Wait before trying again
                $this->sleep(min($sleep * 2, 10));
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Handle signal events for the process.
     *
     * @return void
     */
    protected function handleSignals()
    {
        pcntl_async_signals(true);

        pcntl_signal(SIGTERM, function () {
            $this->shouldQuit = true;
            $this->info('Processor shutdown initiated by SIGTERM signal...');
        });

        pcntl_signal(SIGINT, function () {
            $this->shouldQuit = true;
            $this->info('Processor shutdown initiated by SIGINT signal...');
        });

        pcntl_signal(SIGUSR2, function () {
            $this->shouldQuit = true;
            $this->info('Processor paused by SIGUSR2 signal...');
        });
    }

    /**
     * Sleep for the given number of seconds.
     *
     * @param  int  $seconds
     * @return void
     */
    protected function sleep($seconds)
    {
        if ($seconds < 1) {
            usleep($seconds * 1000000);
        } else {
            sleep($seconds);
        }
    }

    /**
     * Process scheduled tasks that are due.
     *
     * @param  int  $maxAttempts
     * @return void
     */
    protected function processScheduledTasks($maxAttempts)
    {
        try {
            // Get due tasks
            $dueTasks = $this->withRedisRetry(function () {
                return $this->redisDriver->getDueTasks();
            }, $maxAttempts);

            if (empty($dueTasks)) {
                return;
            }

            $this->info(sprintf('Found %d scheduled tasks ready for processing', count($dueTasks)));

            // Process each due task
            foreach ($dueTasks as $taskId) {
                // Push to the deferred queue for immediate processing
                $this->withRedisRetry(function () use ($taskId) {
                    return $this->redisConnection->rpush($this->queuePrefix.'deferred', $taskId);
                }, $maxAttempts);

                $this->info("Scheduled task {$taskId} is now ready for processing");
            }
        } catch (Throwable $e) {
            $this->error('Error processing scheduled tasks: '.$e->getMessage());
        }
    }

    /**
     * Process a single task from Redis.
     *
     * @param  string  $taskId
     * @param  int  $maxAttempts
     * @return void
     */
    protected function processTask($taskId, $maxAttempts)
    {
        $this->info("Processing task: {$taskId}");

        // First try to acquire the lock
        $lockAcquired = false;

        try {
            $lockAcquired = $this->withRedisRetry(function () use ($taskId) {
                return $this->redisDriver->acquireLock($taskId);
            }, $maxAttempts);

            if (! $lockAcquired) {
                $this->info("Task {$taskId} is already being processed by another worker");

                return;
            }

            // We've acquired the lock, now get the task
            $serializedTask = $this->withRedisRetry(function () use ($taskId) {
                return $this->redisConnection->get($taskId.':task');
            }, $maxAttempts);

            if (! $serializedTask) {
                $this->error("Task {$taskId} not found");

                return;
            }

            try {
                // Unserialize and execute the task
                $task = unserialize($serializedTask)->getClosure();
                $result = $task();

                // Store the result
                $this->withRedisRetry(function () use ($taskId, $result) {
                    return $this->redisConnection->set(
                        $taskId.':result',
                        serialize(['result' => $result]),
                        'EX',
                        3600 // Expire in 1 hour
                    );
                }, $maxAttempts);

                $this->info("Task {$taskId} completed successfully");
            } catch (Throwable $e) {
                // Store the error
                $this->withRedisRetry(function () use ($taskId, $e) {
                    return $this->redisConnection->set(
                        $taskId.':result',
                        serialize(['error' => $e->getMessage()]),
                        'EX',
                        3600 // Expire in 1 hour
                    );
                }, $maxAttempts);

                $this->error("Task {$taskId} failed: ".$e->getMessage());
            }
        } catch (Throwable $e) {
            $this->error("Error processing task {$taskId}: ".$e->getMessage());
        } finally {
            // Always release the lock if we acquired it
            if ($lockAcquired) {
                try {
                    $this->withRedisRetry(function () use ($taskId) {
                        $this->redisDriver->releaseLock($taskId);
                    }, $maxAttempts);
                } catch (Throwable $e) {
                    $this->error("Error releasing lock for task {$taskId}: ".$e->getMessage());
                }
            }
        }
    }

    /**
     * Get the Redis connection.
     *
     * @return \Illuminate\Redis\Connections\Connection
     */
    protected function getRedisConnection()
    {
        return $this->redis->connection($this->connection);
    }

    /**
     * Execute a Redis command with automatic retry for connection issues.
     *
     * @param  callable  $callback
     * @param  int  $maxAttempts
     * @return mixed
     *
     * @throws \Throwable
     */
    protected function withRedisRetry(callable $callback, $maxAttempts = 3)
    {
        $attempts = 0;
        $lastException = null;

        while ($attempts < $maxAttempts) {
            try {
                return $callback();
            } catch (Throwable $e) {
                $lastException = $e;
                $attempts++;

                // If we've used up all attempts, rethrow the exception
                if ($attempts >= $maxAttempts) {
                    throw $e;
                }

                $this->error("Redis connection error: {$e->getMessage()}. Retrying ({$attempts}/{$maxAttempts})...");

                // Backoff strategy: exponential with jitter
                $backoff = pow(2, $attempts) + random_int(0, 1000) / 1000;
                $this->sleep(min($backoff, 10));

                // Try to reconnect to Redis
                try {
                    $this->redisConnection = $this->getRedisConnection();
                    $this->info('Redis connection re-established');
                } catch (Throwable $reconnectException) {
                    $this->error("Failed to reconnect to Redis: {$reconnectException->getMessage()}");
                }
            }
        }

        throw $lastException;
    }
}
