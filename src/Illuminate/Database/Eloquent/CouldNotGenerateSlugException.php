<?php

namespace Illuminate\Database\Eloquent;

use Illuminate\Validation\ValidationException;
use RuntimeException;

class CouldNotGenerateSlugException extends RuntimeException
{
    /**
     * Create a new exception instance.
     */
    public function __construct(
        string $message,
        protected string $errorKey,
        protected string $errorMessage,
    ) {
        parent::__construct($message);
    }

    /**
     * Get the exception's context for the handler.
     */
    public function getInnerException(): ValidationException
    {
        return ValidationException::withMessages([
            $this->errorKey => $this->errorMessage,
        ]);
    }
}
