<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrRule implements Rule
{
    public function __construct(protected $rules)
    {
    }

    public function passes($attribute, $value)
    {
        $attribute = str_replace('.', Str::random(), $attribute);

        foreach ($this->rules as $rule) {
            $data = [
                $attribute => $value,
            ];
            $rules = [
                $attribute => $rule,
            ];

            if (Validator::make($data, $rules)->passes()) {
                return true;
            }
        }

        return false;
    }

    public function message()
    {
        return 'None of the specified field rules is applicable.';
    }
}
