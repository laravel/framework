<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Illuminate\Support\Facades\Validator;

trait ValidatesAttributes
{
    /**
     * Whether the model should automatically validate on saving event.
     *
     * @var bool
     */
    protected $validateOnSaving = true;

    /**
     * Model attribute validation rules.
     *
     * @var array
     */
    protected $validationRules = [];

    /**
     * Model attribute validation messages.
     *
     * @var array
     */
    protected $validationMessages = [];

    /**
     * Get whether the model should automatically validate on saving event.
     *
     * @return bool
     */
    public function getValidateOnSaving(): bool
    {
        return $this->validateOnSaving;
    }

    /**
     * Get all validation rules for the model.
     *
     * @return array
     */
    public function getValidationRules(): array
    {
        return $this->validationRules;
    }

    /**
     * Get all validation messages for the model.
     *
     * @return array
     */
    public function getValidationMessages(): array
    {
        return $this->validationMessages;
    }

    /**
     * Execute validation on the model.
     *
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validate(): array
    {
        return Validator::make(
            $this->getAttributes(),
            $this->getValidationRules(),
            $this->getValidationMessages()
        )->validate();
    }

    /**
     * Register validation on model saving event.
     * Validation will only be performed if $validateOnSaving is enabled (enabled by default).
     */
    public static function bootValidatesAttributes()
    {
        static::saving(function ($model) {
            if ($model->getValidateOnSaving()) {
                $model->validate();
            }
        });
    }
}
