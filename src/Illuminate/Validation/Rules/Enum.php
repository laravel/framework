<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\Rule;
use TypeError;

class Enum implements Rule
{
    /**
     * The type of the enum.
     *
     * @var string
     */
    protected $type;

    /**
     * The method used to translate a value into the enum.
     *
     * @var string
     */
    protected $method;

    /**
     * Create a new rule instance.
     *
     * @param  string  $type
     * @param  string  $method
     * @return void
     */
    public function __construct($type, $method = 'tryFrom')
    {
        $this->type = $type;
        $this->method = $method;
    }

    public function method($method)
    {
        $this->method = $method;
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

        if (is_null($value) || ! function_exists('enum_exists') || ! enum_exists($this->type) || ! method_exists($this->type, $this->method)) {
            return false;
        }

        try {
            return ! is_null($this->type::{$this->method}($value));
        } catch (TypeError $e) {
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
