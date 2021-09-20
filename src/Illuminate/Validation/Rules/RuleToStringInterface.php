<?php

namespace Illuminate\Validation\Rules;

interface RuleToStringInterface
{
    /**
     * Convert the rule to a validation string.
     *
     * @return string
     */
    public function __toString();
}
