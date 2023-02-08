<?php

namespace Illuminate\Console;

use Exception;

class ValidationException extends Exception
{
    /**
     * The validator instance.
     *
     * @var \Illuminate\Contracts\Validation\Validator
     */
    public $validator;

    /**
     * Create a new exception instance.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     */
    public function __construct($validator)
    {
        $this->validator = $validator;
    }

    /**
     * Return the validator instance.
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function getValidator()
    {
        return $this->validator;
    }
}
