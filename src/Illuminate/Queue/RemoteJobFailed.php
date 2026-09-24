<?php

namespace Illuminate\Queue;

use RuntimeException;

class RemoteJobFailed extends RuntimeException
{
    /**
     * Create a new exception instance.
     *
     * @param  string  $job
     * @param  string  $message
     * @param  string|null  $type
     */
    public function __construct(public string $job, string $message, public ?string $type = null)
    {
        parent::__construct($message);
    }
}
