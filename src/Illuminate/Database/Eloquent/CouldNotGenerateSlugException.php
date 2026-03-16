<?php

namespace Illuminate\Database\Eloquent;

use RuntimeException;

class CouldNotGenerateSlugException extends RuntimeException
{
    /**
     * Create a new exception instance.
     */
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
