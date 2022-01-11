<?php

namespace Illuminate\Validation\Rules;

use BackedEnum;
use Illuminate\Contracts\Validation\Rule;

class Enum implements Rule
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
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (is_null($value) || ! function_exists('enum_exists') || ! enum_exists($this->type) || ! method_exists($this->type, 'tryFrom')) {
            return false;
        }

        return in_array($value, array_map(fn (BackedEnum $enum) => $enum->value, $this->type::cases()));
    }

    /**
     * Get the validation error message.
     *
     * @return array
     */
    public function message()
    {
        $message = trans('validation.enum');

        return $message === 'validation.enum'
            ? ['The selected :attribute is invalid.']
            : $message;
    }
}
