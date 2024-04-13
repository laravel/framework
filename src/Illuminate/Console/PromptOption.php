<?php

namespace Illuminate\Console;

/**
 * @internal
 */
class PromptOption
{
    /**
     * Create a new prompt option.
     *
     * @param  string|int|null  $value
     * @param  string  $label
     */
    public function __construct(public $value, public $label)
    {
        //
    }

    /**
     * Return the string representation of the option.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->label;
    }

    /**
     * Wrap the given options in PromptOption objects.
     *
     * @param  array  $options
     * @return array
     */
    public static function wrap($options)
    {
        return array_map(
            fn ($label, $value) => new static(array_is_list($options) ? $label : $value, $label),
            $options,
            array_keys($options)
        );
    }

    /**
     * Unwrap the given option(s).
     *
     * @param  static|string|int|array  $option
     * @return string|int|array
     */
    public static function unwrap($option)
    {
        if (is_array($option)) {
            return array_map(static::unwrap(...), $option);
        }

        return $option instanceof static ? $option->value : $option;
    }
}
