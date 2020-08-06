<?php

namespace Illuminate\Tests\Validation\fixtures;

use Illuminate\Contracts\Validation\Rule;

class CustomRuleWithDependency implements Rule
{
    /**
     * @var \Illuminate\Tests\Validation\fixtures\RuleDependency
     */
    protected $dependency;

    public function __construct(RuleDependency $dependency)
    {
        $this->dependency = $dependency;
    }

    public function passes($attribute, $value)
    {
        return $this->dependency->isOk();
    }

    public function message()
    {
        return 'A custom message with dependency.';
    }
}
