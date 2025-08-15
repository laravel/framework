<?php

namespace Illuminate\Tests\Validation\fixtures;

use Illuminate\Validation\Attributes\Validate;
use Illuminate\Validation\Validator;

class ValidatesAttributes extends Validator
{
    public function validateRule($attribute, $value, array $parameters)
    {
        return true;
    }

    #[Validate]
    public function ruleWithAttribute($attribute, $value, array $parameters)
    {
        return true;
    }
}
