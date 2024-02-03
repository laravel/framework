<?php

namespace Illuminate\Concurrency;

use Closure;
use Illuminate\Process\Factory as ProcessFactory;
use Illuminate\Process\Pool;
use Illuminate\Support\Arr;
use Laravel\SerializableClosure\SerializableClosure;

class Factory
{
    /**
     * Create a new concurrency factory instance.
     */
    public function __construct(protected ProcessFactory $processFactory)
    {
        //
    }

    public function run(Closure|array $tasks): array
    {
        $results = $this->processFactory->pool(function (Pool $pool) use ($tasks) {
            foreach (Arr::wrap($tasks) as $task) {
                $pool->path(base_path())->env([
                    'LARAVEL_INVOKABLE_CLOSURE' => serialize(new SerializableClosure($task)),
                ])->command('php artisan invoke-serialized-closure');
            }
        })->start()->wait();

        return $results->collect()->map(function ($result) {
            $result = json_decode($result->output(), true);

            if (! $result['successful']) {
                $exceptionClass = $result['exception'];

                throw new $exceptionClass($result['message']);
            }

            return unserialize($result['result']);
        })->all();
    }

    /**
     * Start the given task(s) in the background.
     */
    public function background(Closure|array $tasks): void
    {
        foreach (Arr::wrap($tasks) as $task) {
            $this->processFactory->path(base_path())->env([
                'LARAVEL_INVOKABLE_CLOSURE' => serialize(new SerializableClosure($task)),
            ])->run('php artisan invoke-serialized-closure 2>&1 &');
        }
    }
}
