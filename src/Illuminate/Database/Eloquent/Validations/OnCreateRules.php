<?php

namespace Illuminate\Database\Eloquent\Validations;

use Attribute;
use Illuminate\Support\Arr;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
class OnCreateRules
{
    public array|string $rules;

    /**
     * Create a new OnUpdateRules instance.
     *
     * @param array|string $rules The rules to apply.
     */
    public function __construct(array|string $rules)
    {
        if (is_array($rules) && Arr::isAssoc($rules)) {
            foreach ($rules as $field => $rule) {
                $this->validateFieldAndRules($field, $rule);
                $this->rules = $rules;
            }
        } elseif (is_string($rules)) {
            $this->rules = $rules;
        } else {
            throw new InvalidArgumentException('Validation rules must be a string or an array.');
        }
    }

    /**
     * Validate the field and its associated rules.
     *
     * @param string $field The field name.
     * @param mixed  $rules The validation rules.
     *
     * @return void
     * @throws InvalidArgumentException
     */
    private function validateFieldAndRules(string $field, mixed $rules): void
    {
        if (empty($field)) {
            throw new InvalidArgumentException('Field name must be a non-empty string.');
        }

        if (!is_string($rules) && !is_array($rules)) {
            throw new InvalidArgumentException('Validation rules must be a string or an array.');
        }

        if (is_array($rules)) {
            foreach ($rules as $rule) {
                if (!is_string($rule)) {
                    throw new InvalidArgumentException('Each rule must be a string.');
                }
            }
        } elseif (!is_string($rules)) {
            throw new InvalidArgumentException('Each rule must be a string.');
        }
    }
}
