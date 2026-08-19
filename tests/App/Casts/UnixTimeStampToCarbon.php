<?php

namespace Illuminate\Tests\App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Carbon;

class UnixTimeStampToCarbon implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        return Carbon::parse($value);
    }

    public function set($model, string $key, $value, array $attributes)
    {
        return Carbon::parse($value)->getTimestamp();
    }
}
