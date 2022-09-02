<?php

namespace Illuminate\Contracts\Validation;

use Illuminate\Validation\ValidationException;

interface DeferrableValidation
{
    /**
     * Determine if validated data is valid.
     *
     * @return bool
     */
    public function isValid(): bool;

    /**
     * Get deferred validation exception.
     *
     * @return \Illuminate\Validation\ValidationException
     */
    public function getValidationException(): ?ValidationException;
}
