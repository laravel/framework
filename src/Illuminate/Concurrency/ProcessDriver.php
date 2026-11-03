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
use Illuminate\Support\Str;
use Laravel\SerializableClosure\SerializableClosure;
use RuntimeException;

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

        $envelopeKeys = [];

        $results = $this->processFactory->pool(function (Pool $pool) use ($tasks, $command, &$envelopeKeys) {
            foreach (Arr::wrap($tasks) as $key => $task) {
                $envelopeKeys[$key] = bin2hex(random_bytes(32));

                $pool->as($key)->path(base_path())->env([
                    'LARAVEL_CONCURRENCY_HMAC_KEY' => $envelopeKeys[$key],
                    'LARAVEL_INVOKABLE_CLOSURE' => base64_encode(
                        serialize(new SerializableClosure($task))
                    ),
                ])->command($command);
            }
        })->start()->wait();

        return $results->collect()->mapWithKeys(function ($result, $key) use ($envelopeKeys) {
            if ($result->failed()) {
                throw new Exception('Concurrent process failed with exit code ['.$result->exitCode().']. Message: '.$result->errorOutput());
            }

            $result = $this->parseEnvelope($result->output(), $envelopeKeys[$key]);

            if (! $result['successful']) {
                throw new $result['exception'](
                    ...(! empty(array_filter($result['parameters']))
                        ? $result['parameters']
                        : [$result['message']])
                );
            }

            return [$key => unserialize($result['result'])];
        })->all();
    }

    /**
     * Extract the signed result envelope from the given process output.
     *
     * @throws \RuntimeException
     */
    protected function parseEnvelope(string $output, string $key): array
    {
        $marker = '@@LARAVEL-CONCURRENCY-V1@@';
        $length = strlen($output);
        $position = strrpos($output, $marker);

        while ($position !== false) {
            if (preg_match('/\G([A-Za-z0-9+\/=]+)@@([0-9a-f]{64})@@/', $output, $matches, 0, $position + strlen($marker)) &&
                hash_equals(hash_hmac('sha256', $matches[1], $key), $matches[2])) {
                $payload = base64_decode($matches[1], true);

                $result = is_string($payload) ? json_decode($payload, true) : null;

                if (is_array($result) && array_key_exists('successful', $result)) {
                    return $result;
                }
            }

            $position = $position === 0 ? false : strrpos($output, $marker, $position - $length - 1);
        }

        throw new RuntimeException('The concurrent process did not return a valid result envelope. Output: '.Str::limit(trim($output), 200));
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
