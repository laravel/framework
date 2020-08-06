<?php

namespace Illuminate\Tests\Validation\fixtures;

use Illuminate\Contracts\Validation\Rule;

class CustomRule implements Rule
{
    public function passes($attribute, $value)
    {
        return true;
    }

    public function message()
    {
        return 'A custom message.';
    }
}
