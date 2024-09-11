<?php

namespace Illuminate\Contracts\Concurrency;

use Closure;

interface Driver
{
    /**
     * Run the given tasks concurrently and return an array containing the results.
     */
    public function run(Closure|array $tasks): array;

    /**
     * Start the given tasks in the background.
     */
    public function background(Closure|array $tasks): void;
}
