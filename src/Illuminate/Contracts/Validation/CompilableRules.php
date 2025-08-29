<?php

namespace Illuminate\Contracts\Validation;

interface CompilableRules
{
    /**
     * Compile the object into usable rules.
     *
     * @param  string  $attribute
     * @return \stdClass
     */
    public function compile($attribute, $value, $data = null, $context = null);
}
