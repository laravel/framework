<?php

namespace Illuminate\Contracts\Validation;

use Illuminate\Validation\Validator;

interface ValidatorAwareRule
{
    /**
     * Set the current validator.
     *
     * @return $this
     */
    public function setValidator(Validator $validator);
}
