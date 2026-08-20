<?php

namespace Illuminate\Concurrency;

use RuntimeException;
use Throwable;

class CapturedTaskException extends RuntimeException
{
    /**
     * Create a new captured task exception instance.
     *
     * The original task exception has already been captured in the task's
     * result envelope for the caller. This wrapper is rethrown so that the
     * failure remains visible to the queue's failed job machinery.
     */
    public function __construct(Throwable $previous)
    {
        parent::__construct($previous->getMessage(), 0, $previous);
    }
}
