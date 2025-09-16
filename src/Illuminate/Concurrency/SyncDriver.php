<?php

namespace Illuminate\Concurrency;

use Closure;
use Illuminate\Contracts\Concurrency\Driver;
use Illuminate\Support\Collection;
use Illuminate\Support\Defer\DeferredCallback;

use function Illuminate\Support\defer;

class SyncDriver implements Driver
{
    /**
     * Run the given tasks concurrently and return an array containing the results.
     */
    public function run(Closure|array $tasks): array
    {
        return Collection::wrap($tasks)->map(function ($task, $key) {
            try {
                return $task();
            } catch (\Throwable $e) {
                throw new \Exception("Synchronous task [{$key}] failed: ".$e->getMessage(), 0, $e);
            }
        })->all();
    }

    /**
     * Start the given tasks in the background after the current task has finished.
     */
    public function defer(Closure|array $tasks): DeferredCallback
    {
        return defer(fn () => Collection::wrap($tasks)->each(fn ($task) => $task()));
    }
}
