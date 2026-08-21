<?php

namespace Illuminate\Concurrency;

use RuntimeException;

class TaskTimedOutException extends RuntimeException
{
    public function __construct(
        public int $received,
        public int $total,
        public int $seconds,
        public ?string $connection = null,
        public ?string $queue = null,
        public ?string $store = null,
    ) {
        parent::__construct(sprintf(
            'Concurrency tasks dispatched to the [%s] queue connection%s timed out after %d seconds with [%d/%d] results received. Ensure queue workers are running and writing results to the [%s] cache store.',
            $connection,
            is_null($queue) ? '' : sprintf(' on the [%s] queue', $queue),
            $seconds,
            $received,
            $total,
            $store,
        ));
    }
}
