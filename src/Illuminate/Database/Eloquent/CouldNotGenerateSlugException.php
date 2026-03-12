<?php

namespace Illuminate\Database\Eloquent;

use RuntimeException;

class CouldNotGenerateSlugException extends RuntimeException
{
    /**
     * Create a new exception instance.
     */
    public function __construct(string $message = 'Could not generate a slug.')
    {
        parent::__construct($message);
    }
}
