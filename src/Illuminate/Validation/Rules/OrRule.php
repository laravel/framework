<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrRule implements Rule
{
    public function __construct(protected $rule, protected $orRule)
    {
    }

    public function passes($attribute, $value)
    {
        $attribute = str_replace('.', Str::random(), $attribute);

        $data = [
            $attribute => $value,
        ];
        $rules = [
            $attribute => $this->rule,
        ];
        $orRules = [
            $attribute => $this->orRule,
        ];

        return Validator::make($data, $rules)->passes()
            || Validator::make($data, $orRules)->passes();
    }

    public function message()
    {
        return 'None of the specified field rules is applicable.';
    }
}
