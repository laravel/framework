<?php

namespace Illuminate\Validation\Rules;

class NotInRule extends InRule
{
    public function passes($attribute, $value, $parameters, $validator)
    {
        return ! parent::passes($attribute, $value, $parameters, $validator);
    }
}
