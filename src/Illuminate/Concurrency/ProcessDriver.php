<?php

namespace Illuminate\Concurrency;

use Carbon\CarbonInterval;
use Closure;
use Exception;
use Illuminate\Console\Application;
use Illuminate\Contracts\Concurrency\Driver;
use Illuminate\Process\Factory as ProcessFactory;
use Illuminate\Process\Pool;
use Illuminate\Support\Arr;
use Illuminate\Support\Defer\DeferredCallback;
use Illuminate\Support\Facades\Context;
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
     *
     * @throws \Throwable
     */
    public function run(Closure|array $tasks, CarbonInterval|int|null $timeout = null): array
    {
        $command = Application::formatCommandString('invoke-serialized-closure');

        $results = $this->processFactory->pool(function (Pool $pool) use ($tasks, $command, $timeout) {
            foreach (Arr::wrap($tasks) as $key => $task) {
                $process = $pool->as($key)->path(base_path())->env([
                    'LARAVEL_CONCURRENCY_HMAC_KEY' => (string) app('config')->get('app.key'),
                    /** @phpstan-ignore staticMethod.notFound */
                    '__LARAVEL_CONTEXT' => json_encode(Context::dehydrate()),
                    'LARAVEL_INVOKABLE_CLOSURE' => base64_encode(
                        serialize(new SerializableClosure($task))
                    ),
                ])->command($command);

                if (! is_null($timeout)) {
                    $process->timeout($timeout);
                }
            }
        })->start()->wait();

        return $results->collect()->mapWithKeys(function ($result, $key) {
            if ($result->failed()) {
                throw new Exception('Concurrent process failed with exit code ['.$result->exitCode().']. Message: '.$result->errorOutput());
            }

            $output = $result->output();

            $result = $this->parseEnvelope($output);

            if (! $result['successful']) {
                throw new $result['exception'](
                    ...(! empty(array_filter($result['parameters'], fn ($parameter) => ! is_null($parameter)))
                        ? $result['parameters']
                        : [$result['message']])
                );
            }

            return [$key => unserialize($result['result'])];
        })->all();
    }

    /**
     * Extract the authenticated result envelope from the child process output.
     *
     * Task output may contain arbitrary bytes (including attacker-controlled
     * text), so only a sentinel-framed, HMAC-signed envelope is trusted: the
     * scan runs bottom-up and accepts the last line carrying a valid MAC —
     * task output cannot produce one, and the child always writes the real
     * envelope last.
     */
    protected function parseEnvelope(string $output, ?string $key = null): array
    {
        $key = $key ?? (string) app('config')->get('app.key');
        $lines = preg_split('/\r?\n/', $output) ?: [];

        for ($i = count($lines) - 1; $i >= 0; $i--) {
            if (! preg_match('/@@LARAVEL-CONCURRENCY-V1@@([A-Za-z0-9+\/=]+)@@([0-9a-f]{64})@@/', $lines[$i], $match)) {
                continue;
            }

            if (! hash_equals(hash_hmac('sha256', $match[1], $key), $match[2])) {
                continue;
            }

            $payload = base64_decode($match[1], true);

            $result = is_string($payload) ? json_decode($payload, true) : null;

            if (is_array($result) && array_key_exists('successful', $result)) {
                return $result;
            }
        }

        throw new \RuntimeException('The concurrency child process did not return a valid result envelope.');
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
                    'LARAVEL_CONCURRENCY_HMAC_KEY' => (string) app('config')->get('app.key'),
                    /** @phpstan-ignore staticMethod.notFound */
                    '__LARAVEL_CONTEXT' => json_encode(Context::dehydrate()),
                    'LARAVEL_INVOKABLE_CLOSURE' => base64_encode(
                        serialize(new SerializableClosure($task))
                    ),
                ])->run($command.' 2>&1 &');
            }
        });
    }
}
