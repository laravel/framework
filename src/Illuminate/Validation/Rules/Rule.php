<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Support\Str;
use Illuminate\Contracts\Validation\Rule as RuleContract;

abstract class Rule implements RuleContract
{
    /**
     * The name of the rule.
     */
    protected $rule;

    /**
     * Get the default validation rule's name.
     *
     * @return string
     */
    public function name()
    {
        return $this->rule ?: Str::snake(class_basename($this));
    }
}
