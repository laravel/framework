<?php

namespace Illuminate\Concurrency;

use RuntimeException;

class TaskTimedOutException extends RuntimeException
{
    /**
     * Create a new task timeout exception instance.
     */
    public function __construct(
        public int $received,
        public int $total,
        public int $seconds,
        public ?string $connection = null,
        public ?string $queue = null,
        public ?string $store = null,
    ) {
        parent::__construct(sprintf(
            'Concurrency tasks dispatched to the [%s] queue connection%s timed out after %d %s with [%d/%d] results received. Ensure queue workers are running and share the [%s] cache store with this process.',
            $connection ?? 'default',
            is_null($queue) ? '' : sprintf(' on the [%s] queue', $queue),
            $seconds,
            $seconds === 1 ? 'second' : 'seconds',
            $received,
            $total,
            $store ?? 'default',
        ));
    }
}
