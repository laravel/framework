<?php

namespace Illuminate\Concurrency;

use Closure;
use Illuminate\Support\Arr;

class SyncDriver
{
    /**
     * Run the given tasks concurrently and return an array containing the results.
     */
    public function run(Closure|array $tasks): array
    {
        return collect(Arr::wrap($tasks))->map(
            fn ($task) => $task()
        )->all();
    }

    /**
     * Start the given tasks in the background.
     */
    public function background(Closure|array $tasks): void
    {
        collect(Arr::wrap($tasks))->each(fn ($task) => $task());
    }
}
