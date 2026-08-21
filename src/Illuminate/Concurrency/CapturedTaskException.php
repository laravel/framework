<?php

namespace Illuminate\Concurrency;

use RuntimeException;
use Throwable;

/**
 * The original task exception has already been captured in the task's
 * result envelope for the caller. This wrapper is rethrown so that the
 * failure remains visible to the queue's failed job machinery.
 */
class CapturedTaskException extends RuntimeException
{
    public function __construct(Throwable $previous)
    {
        parent::__construct($previous->getMessage(), 0, $previous);
    }
}
