<?php

namespace Illuminate\Console\Concerns;

use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

trait ValidatesInput
{
    /**
     * Determine if the command has validation rules.
     */
    protected function hasCommandValidationRules(): bool
    {
        return ! empty($this->rules());
    }

    /**
     * Get the validator instance for the command input.
     */
    protected function getCommandInputValidator(): ValidatorContract
    {
        return $this->laravel->make(ValidationFactory::class)->make(
            $this->validationData(),
            $this->rules(),
            $this->messages(),
            $this->attributes(),
        );
    }

    /**
     * Get the validator instance when command input is invalid.
     */
    protected function getFailedCommandInputValidator(): ?ValidatorContract
    {
        if (! $this->hasCommandValidationRules()) {
            return null;
        }

        $validator = $this->getCommandInputValidator();

        return $validator->fails() ? $validator : null;
    }

    /**
     * Display failed validation messages for the given validator.
     */
    protected function displayValidationErrors(ValidatorContract $validator): void
    {
        foreach ($validator->errors()->all() as $error) {
            $this->components->error($error);
        }
    }

    /**
     * Get the command input data used for validation.
     *
     * @return array<string, mixed>
     */
    protected function validationData(): array
    {
        $arguments = $this->arguments();
        $options = $this->options();

        unset($arguments['command']);

        $prefixedOptions = [];

        foreach ($options as $key => $value) {
            $prefixedOptions["--{$key}"] = $value;
        }

        return array_merge(
            $arguments,
            $options,
            $prefixedOptions,
            [
                'arguments' => $arguments,
                'options' => array_merge($options, $prefixedOptions),
            ],
        );
    }

    /**
     * Get the validation rules that apply to the command input.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [];
    }

    /**
     * Get custom validation messages for command input validation.
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [];
    }

    /**
     * Get custom validation attributes for command input validation.
     *
     * @return array<string, string>
     */
    protected function attributes(): array
    {
        return [];
    }
}
