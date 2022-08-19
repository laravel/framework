<?php

namespace Illuminate\Validation\Rules;

class RawRule
{
    /**
     * The validation rule.
     *
     * @var string
     */
    public $rule;

    /**
     * The validation rule parameters.
     *
     * @var array
     */
    public $parameters = [];
    
    /**
     * Constructor.
     *
     * @param  string  $rule
     * @param  array  $parameters
     * @return void
     */
    public function __construct($rule, array $parameters)
    {
        $this->rule = $rule;
        $this->parameters = $parameters;
    }
}
