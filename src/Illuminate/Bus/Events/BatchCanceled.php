<?php

namespace Illuminate\Bus\Events;

use Illuminate\Bus\Batch;
use Throwable;

class BatchCanceled
{
    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Bus\Batch  $batch  The batch instance.
     * @param  \Throwable|null  $exception  The exception that caused the cancellation.
     */
    public function __construct(
        public Batch $batch,
        public ?Throwable $exception = null,
    ) {
    }
}
