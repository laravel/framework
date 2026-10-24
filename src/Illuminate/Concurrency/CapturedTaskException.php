<?php

namespace Illuminate\Concurrency;

use RuntimeException;
use Throwable;

/**
 * The original task exception has already been stored for the caller.
 * This exception is thrown so the queue worker records the failed job.
 */
class CapturedTaskException extends RuntimeException
{
    /**
     * Create a new exception instance.
     */
    public function __construct(Throwable $previous)
    {
        parent::__construct($previous->getMessage(), 0, $previous);
    }
}
