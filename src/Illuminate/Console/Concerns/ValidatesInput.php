<?php

namespace Illuminate\Console\Concerns;

use Illuminate\Console\ValidationException;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;

trait ValidatesInput
{
    /**
     * The rules to use for input validation.
     *
     * @return array
     */
    protected function rules()
    {
        return [];
    }

    /**
     * The custom error messages to use for input validation.
     *
     * @return array
     */
    protected function messages()
    {
        return [];
    }

    /**
     * The custom attribute names to use for input validation.
     *
     * @return array
     */
    protected function attributes()
    {
        return [];
    }

    /**
     * Validate the input as defined by the command.
     *
     * @return void
     *
     * @throws \Illuminate\Console\ValidationException
     */
    protected function validateInput()
    {
        if (empty($rules = $this->rules())) {
            return;
        }

        $validator = $this->laravel->make(ValidatorFactory::class)->make(
            array_merge($this->arguments(), $this->options()),
            $rules,
            $this->messages(),
            $this->attributes()
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Display the validation errors for the given validator.
     *
     * @param  \Illuminate\Console\ValidationException  $validator
     * @return void
     */
    protected function displayFailedValidationErrors($validator)
    {
        foreach ($validator->errors()->all() as $error) {
            $this->components->error($error);
        }
    }
}
