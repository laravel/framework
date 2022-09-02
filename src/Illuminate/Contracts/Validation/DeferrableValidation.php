<?php

namespace Illuminate\Contracts\Validation;

interface DeferrableValidation
{
    /**
     * Determine if validated data is valid.
     *
     * @return bool
     */
    public function isValid(): bool;
}
