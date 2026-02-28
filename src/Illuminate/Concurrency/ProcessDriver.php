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

/**
 * @phpstan-type InvokeSerializedClosureSuccessPayload array{
 *     successful: true,
 *     result: string
 * }
 * @phpstan-type InvokeSerializedClosureFailurePayload array{
 *     successful: false,
 *     exception: class-string<\Throwable>,
 *     message: string,
 *     file: string,
 *     line: int,
 *     parameters: array<string, mixed>
 * }
 * @phpstan-type InvokeSerializedClosurePayload InvokeSerializedClosureSuccessPayload|InvokeSerializedClosureFailurePayload
 */
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
     *
     * @param  \Closure(): mixed|array<array-key, \Closure(): mixed>  $tasks
     * @return array<array-key, mixed>
     */
    public function run(Closure|array $tasks): array
    {
        $command = Application::formatCommandString('invoke-serialized-closure');

        /** @var array<array-key, \Closure(): mixed> $tasks */
        $tasks = Arr::wrap($tasks);

        $results = $this->processFactory->pool(function (Pool $pool) use ($tasks, $command) {
            foreach ($tasks as $key => $task) {
                $pool->as($key)->path(base_path())->env([
                    'LARAVEL_INVOKABLE_CLOSURE' => base64_encode(
                        serialize(new SerializableClosure($task))
                    ),
                ])->command($command);
            }
        })->start()->wait();

        return $results->collect()->mapWithKeys(function ($processResult, $key) {
            if ($processResult->failed()) {
                throw new Exception('Concurrent process failed with exit code ['.$processResult->exitCode().']. Message: '.$processResult->errorOutput());
            }

            /** @var InvokeSerializedClosurePayload $payload */
            $payload = json_decode($processResult->output(), true);

            if (! $payload['successful']) {
                throw new $payload['exception'](
                    ...(! empty(array_filter($payload['parameters']))
                        ? $payload['parameters']
                        : [$payload['message']])
                );
            }

            return [$key => unserialize($payload['result'])];
        })->all();
    }

    /**
     * Start the given tasks in the background after the current task has finished.
     *
     * @param  \Closure(): mixed|array<array-key, \Closure(): mixed>  $tasks
     */
    public function defer(Closure|array $tasks): DeferredCallback
    {
        $command = Application::formatCommandString('invoke-serialized-closure');

        return defer(function () use ($tasks, $command) {
            /** @var array<array-key, \Closure(): mixed> $tasks */
            $tasks = Arr::wrap($tasks);

            foreach ($tasks as $task) {
                $this->processFactory->path(base_path())->env([
                    'LARAVEL_INVOKABLE_CLOSURE' => base64_encode(
                        serialize(new SerializableClosure($task))
                    ),
                ])->run($command.' 2>&1 &');
            }
        });
    }
}
