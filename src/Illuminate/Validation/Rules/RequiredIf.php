<?php

namespace Illuminate\Validation\Rules;

use Closure;

class RequiredIf
{
    /**
     * The callback that validates the attribute.
     *
     * @var \Closure
     */
    public $callback;

    /**
     * Create a new Closure based required validation rule.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function __construct($callback)
    {
        $this->callback = $callback;
    }

    /**
     * Convert the rule to a validation string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->callback->__invoke() ? 'required' : '';
    }
}
