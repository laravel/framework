<?php

namespace Illuminate\Validation\Rules;

use BackedEnum;
use Illuminate\Contracts\Validation\Rule;
use TypeError;
use UnitEnum;

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
        if ($value instanceof $this->type) {
            return true;
        }

        if (is_null($value) || ! function_exists('enum_exists') || ! enum_exists($this->type)) {
            return false;
        }

        if (! is_subclass_of($this->type, BackedEnum::class)) {
            return defined($this->type.'::'.$value) and constant($this->type.'::'.$value) instanceof UnitEnum;
        }

        try {
            return ! is_null($this->type::tryFrom($value));
        } catch (TypeError) {
            return false;
        }
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
