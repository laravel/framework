<?php

namespace Illuminate\Concurrency;

use Closure;
use Illuminate\Foundation\Defer\DeferredCallback;
use Illuminate\Process\Factory as ProcessFactory;
use Illuminate\Process\Pool;
use Illuminate\Support\Arr;
use Laravel\SerializableClosure\SerializableClosure;
use Symfony\Component\Process\PhpExecutableFinder;

class ProcessDriver
{
    /**
     * Create a new process based concurrency driver.
     */
    public function __construct(protected ProcessFactory $processFactory)
    {
        //
    }

    /**
     * Run the given tasks concurrently and return an array containing the results.
     */
    public function run(Closure|array $tasks): array
    {
        $php = (new PhpExecutableFinder)->find(false);

        $results = $this->processFactory->pool(function (Pool $pool) use ($tasks, $php) {
            foreach (Arr::wrap($tasks) as $task) {
                $pool->path(base_path())->env([
                    'LARAVEL_INVOKABLE_CLOSURE' => serialize(new SerializableClosure($task)),
                ])->command($php.' artisan invoke-serialized-closure');
            }
        })->start()->wait();

        return $results->collect()->map(function ($result) {
            $result = json_decode($result->output(), true);

            if (! $result['successful']) {
                throw new $result['exception'](
                    $result['message']
                );
            }

            return unserialize($result['result']);
        })->all();
    }

    /**
     * Start the given tasks in the background after the current task has finished.
     */
    public function defer(Closure|array $tasks): DeferredCallback
    {
        return defer(function () use ($tasks) {
            foreach (Arr::wrap($tasks) as $task) {
                $this->processFactory->path(base_path())->env([
                    'LARAVEL_INVOKABLE_CLOSURE' => serialize(new SerializableClosure($task)),
                ])->run('php artisan invoke-serialized-closure 2>&1 &');
            }
        });
    }
}
