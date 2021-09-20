<?php

namespace Illuminate\Validation\Rules;

interface RulesInterface
{
    /**
     * Convert the rule to a validation string.
     *
     * @return string
     */
    public function __toString();
}
