<?php

namespace Illuminate\Concurrency;

class Factory
{
    /**
     * Create a new concurrency factory instance.
     */
    public function __construct(protected Factory $processFactory)
    {
        //
    }

    /**
     * Start the given task(s) in the background.
     */
    public function background(Closure|array $tasks): void
    {

    }
}
