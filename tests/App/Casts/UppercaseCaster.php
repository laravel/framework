<?php

namespace Illuminate\Tests\App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class UppercaseCaster implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        return strtoupper($value);
    }

    public function set($model, $key, $value, $attributes)
    {
        return [$key => strtoupper($value)];
    }
}
