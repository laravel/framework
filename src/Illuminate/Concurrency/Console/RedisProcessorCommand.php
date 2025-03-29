<?php

namespace Illuminate\Concurrency\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Support\InteractsWithTime;

class RedisProcessorCommand extends Command
{
    use InteractsWithTime;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'concurrency:redis-processor 
                            {--connection=default : The Redis connection to use}
                            {--queue-prefix=laravel:concurrency: : The queue prefix to use}
                            {--timeout=60 : The number of seconds to run the processor}
                            {--sleep=1 : The number of seconds to sleep when no jobs are found}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process concurrent tasks from Redis queue';

    /**
     * Execute the console command.
     *
     * @param  \Illuminate\Contracts\Redis\Factory  $redis
     * @return int
     */
    public function handle(RedisFactory $redis)
    {
        $connection = $this->option('connection');
        $queuePrefix = $this->option('queue-prefix');
        $timeout = (int) $this->option('timeout');
        $sleep = (int) $this->option('sleep');

        $this->info("Processing Redis concurrency tasks on [{$connection}]");

        $redis = $redis->connection($connection);
        $shouldRun = true;
        $startTime = $this->currentTime();

        while ($shouldRun) {
            // Process tasks from the queue
            $taskId = $redis->lpop($queuePrefix.'queue');

            if ($taskId) {
                $this->processTask($redis, $taskId);
            } else {
                // Process deferred tasks if there are no immediate tasks
                $deferredTaskId = $redis->lpop($queuePrefix.'deferred');

                if ($deferredTaskId) {
                    $this->processTask($redis, $deferredTaskId);
                } else {
                    // No tasks found, sleep for a while
                    sleep($sleep);
                }
            }

            // Check if we should exit based on timeout
            if ($timeout > 0 && $this->currentTime() - $startTime >= $timeout) {
                $shouldRun = false;
                $this->info('Timeout reached, stopping processor');
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Process a single task from Redis.
     *
     * @param  \Illuminate\Redis\Connections\Connection  $redis
     * @param  string  $taskId
     * @return void
     */
    protected function processTask($redis, $taskId)
    {
        $this->info("Processing task: {$taskId}");

        // Get the task from Redis
        $serializedTask = $redis->get($taskId.':task');

        if (! $serializedTask) {
            $this->error("Task {$taskId} not found");

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

            $this->info("Task {$taskId} completed successfully");
        } catch (Exception $e) {
            // Store the error
            $redis->set(
                $taskId.':result',
                serialize(['error' => $e->getMessage()]),
                'EX',
                3600 // Expire in 1 hour
            );

            $this->error("Task {$taskId} failed: ".$e->getMessage());
        }
    }
}
