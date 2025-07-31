<?php

namespace Illuminate\Concurrency;

use Closure;
use Exception;
use Illuminate\Console\Application;
use Illuminate\Contracts\Concurrency\Driver;
use Illuminate\Process\Factory as ProcessFactory;
use Illuminate\Process\Pool;
use Illuminate\Support\Arr;
use Illuminate\Support\Defer\DeferredCallback;
use Laravel\SerializableClosure\SerializableClosure;

use function Illuminate\Support\defer;

class ProcessDriver implements Driver
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
    public function run(Closure|array $tasks, int $timeout = null): array
    {
        $command = Application::formatCommandString('invoke-serialized-closure');

        $results = $this->processFactory->pool(function (Pool $pool) use ($tasks, $command, $timeout) {
            foreach (Arr::wrap($tasks) as $key => $task) {
                $process = $pool->as($key)->path(base_path())->env([
                    'LARAVEL_INVOKABLE_CLOSURE' => base64_encode(
                        serialize(new SerializableClosure($task))
                    ),
                ])->command($command);
                
                if ($timeout !== null) {
                    $process->timeout($timeout);
                }
            }
        })->start()->wait();

        return $results->collect()->mapWithKeys(function ($result, $key) {
            if ($result->failed()) {
                $errorMessage = $result->errorOutput() ?: 'Process failed with no error output';
                throw new Exception("Concurrent process [{$key}] failed with exit code [{$result->exitCode()}]. Error: {$errorMessage}");
            }

            $output = $result->output();
            if (empty($output)) {
                throw new Exception("Concurrent process [{$key}] produced no output");
            }

            $result = json_decode($output, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Concurrent process [{$key}] produced invalid JSON output: " . json_last_error_msg());
            }

            if (! $result['successful']) {
                $exceptionClass = $result['exception'] ?? Exception::class;
                $message = $result['message'] ?? 'Unknown error occurred';
                $parameters = $result['parameters'] ?? [];

                // Ensure exception class exists
                if (! class_exists($exceptionClass)) {
                    throw new Exception("Process [{$key}] failed with unknown exception class: {$exceptionClass}. Message: {$message}");
                }

                throw new $exceptionClass(
                    ...(! empty(array_filter($parameters))
                        ? $parameters
                        : [$message])
                );
            }

            return [$key => unserialize($result['result'])];
        })->all();
    }

    /**
     * Start the given tasks in the background after the current task has finished.
     */
    public function defer(Closure|array $tasks): DeferredCallback
    {
        $command = Application::formatCommandString('invoke-serialized-closure');

        return defer(function () use ($tasks, $command) {
            foreach (Arr::wrap($tasks) as $task) {
                $this->processFactory->path(base_path())->env([
                    'LARAVEL_INVOKABLE_CLOSURE' => base64_encode(
                        serialize(new SerializableClosure($task))
                    ),
                ])->run($command.' 2>&1 &');
            }
        });
    }
}
