<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\ValidationRule;
use TypeError;
use Closure;

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
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value instanceof $this->type) {
            return;
        }

        if (! is_null($value) && enum_exists($this->type) && method_exists($this->type, 'tryFrom')) {
            try {
                if (! is_null($this->type::tryFrom($value))) {
                    return;
                }
            } catch (TypeError) {
            }
        }

        $fail('validation.enum')->translate([
            'attribute' => $attribute,
        ]);
    }
}
