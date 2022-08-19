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
     * The validation rule arguments.
     *
     * @var array
     */
    public $args = [];
    
    /**
     * Constructor.
     *
     * @param  string  $rule
     * @param  array  $args
     * @return void
     */
    public function __construct($rule, array $args)
    {
        $this->rule = $rule;
        $this->args = $args;
    }
}
