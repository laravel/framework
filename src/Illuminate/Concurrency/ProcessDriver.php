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
    public function run(Closure|array $tasks): array
    {
        $command = Application::formatCommandString('invoke-serialized-closure');

        $results = $this->processFactory->pool(function (Pool $pool) use ($tasks, $command) {
            foreach (Arr::wrap($tasks) as $task) {
                $pool->path(base_path())->env([
                    'LARAVEL_INVOKABLE_CLOSURE' => serialize(new SerializableClosure($task)),
                ])->command($command);
            }
        })->start()->wait();

        return $results->collect()->map(function ($result) {
            if ($result->failed()) {
                throw new Exception('Concurrent process failed with exit code ['.$result->exitCode().']. Message: '.$result->errorOutput());
            }

            $result = json_decode($result->output(), true);

            if (! $result['successful']) {
                $exceptionClass = $result['exception'];
                $reflection = new \ReflectionClass($exceptionClass);

                $args = $result['params'] ?? [];

                if (empty($args)) {
                    throw new $result['exception'](
                        $result['message']
                    );
                }

                $constructor = $reflection->getConstructor();
                if ($constructor) {
                    $constructorParams = $constructor->getParameters();
                    $finalArgs = [];

                    foreach ($constructorParams as $param) {
                        $paramName = $param->getName();
                        $finalArgs[] = $args[$paramName] ?? ($param->isOptional() ? $param->getDefaultValue() : null);
                    }
                } else {
                    $finalArgs = [];
                }

                throw $reflection->newInstanceArgs($finalArgs);
            }

            return unserialize($result['result']);
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
                    'LARAVEL_INVOKABLE_CLOSURE' => serialize(new SerializableClosure($task)),
                ])->run($command.' 2>&1 &');
            }
        });
    }
}
