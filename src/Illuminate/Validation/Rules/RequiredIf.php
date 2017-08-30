<?php

namespace Illuminate\Validation\Rules;

class RequiredIf
{
    /**
     * The name of the rule.
     */
    protected $rule = 'required_if';

    /**
     * The constraint field.
     *
     * @var string
     */
    protected $field;

    /**
     * The constraint value.
     *
     * @var string
     */
    protected $value;

    /**
     * Create a new required_if rule instance.
     *
     * @param  string  $field
     * @param  string  $value
     * @return void
     */
    public function __construct(string $field, string $value)
    {
        $this->field = $field;
        $this->value = $value;
    }

    /**
     * Convert the rule to a validation string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->rule.':'.$this->field.','.$this->value;
    }
}
