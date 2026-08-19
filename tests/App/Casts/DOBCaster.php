<?php

namespace Illuminate\Tests\App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Carbon;

class DOBCaster implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        if ($value === null) {
            return null;
        }

        return Carbon::parse($value);
    }

    public function set($model, $key, $value, $attributes)
    {
        if ($value instanceof Carbon) {
            return [$key => $value->toDateString()];
        }

        if ($value === null) {
            return [$key => null];
        }

        return [$key => (string) $value];
    }
}
