<?php

namespace Illuminate\Validation\Rules;

class Distinct
{
    /**
     * The name of the rule.
     *
     * @var string
     */
    protected $rule = 'distinct';

    /**
     * Use strict mode.
     *
     * @var bool
     */
    protected $strict;

    /**
     * Ignore case sensitive.
     *
     * @var bool
     */
    protected $ignoreCase;

    /**
     * Create a new in rule instance.
     *
     * @param  bool  $strict
     * @return void
     */
    public function __construct(bool $strict = false, bool $ignoreCase = false)
    {
        $this->strict = $strict;
        $this->ignoreCase = $ignoreCase;
    }

    /**
     * Convert the rule to a validation string.
     *
     * @return string
     *
     * @see \Illuminate\Validation\ValidationRuleParser::parseParameters
     */
    public function __toString()
    {
        if ($this->ignoreCase) {
            $mode = 'ignore_case';
        } else {
            $mode = $this->strict ? 'strict' : null;
        }

        return $this->rule.($mode ? ':'.$mode : null);
    }
}
