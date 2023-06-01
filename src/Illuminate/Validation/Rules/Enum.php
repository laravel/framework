<?php

namespace Illuminate\Validation\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use TypeError;

class Enum implements ValidationRule
{
    /**
     * The type of the enum.
     *
     * @var string
     */
    protected $type;

    /**
     * Create a new rule instance.
     *
     * @param  string  $type
     * @return void
     */
    public function __construct($type)
    {
        $this->type = $type;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value instanceof $this->type) {
            return;
        }

        try {
            $enum = enum_exists($this->type) ? $this->type : null;

            if ($enum === null || ! method_exists($enum, 'tryFrom') || is_null($enum::tryFrom($value))) {
                $fail('validation.enum')->translate([
                    'value' => $value,
                ]);
            }
        } catch (TypeError) {
            $fail('validation.enum')->translate([
                'value' => $value,
            ]);
        }
    }
}
