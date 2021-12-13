<?php

namespace Illuminate\Validation\Rules;

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

        return ! is_null($this->type::tryFrom($value));
    }

    /**
     * Get the validation error message.
     *
     * @return array
     */
    public function message()
    {
        return [
            'The selected :attribute is invalid.',
        ];
    }
}
