<?php

namespace Illuminate\Tests\App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Carbon;

class DateTimezoneCasterWithObjectCaching implements CastsAttributes
{
    public function __construct(private string $timezone = 'UTC')
    {
    }

    public function get($model, $key, $value, $attributes)
    {
        return Carbon::parse($value, $this->timezone);
    }

    public function set($model, $key, $value, $attributes)
    {
        return $value->timezone($this->timezone)->format('Y-m-d');
    }
}
