<?php

namespace Illuminate\Tests\App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\DeviatesCastableAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Tests\App\ValueObjects\Decimal;

class DecimalCaster implements CastsAttributes, DeviatesCastableAttributes, SerializesCastableAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        return new Decimal($value);
    }

    public function set($model, $key, $value, $attributes)
    {
        return (string) $value;
    }

    public function increment($model, $key, $value, $attributes)
    {
        return new Decimal($attributes[$key] + ($value instanceof Decimal ? (string) $value : $value));
    }

    public function decrement($model, $key, $value, $attributes)
    {
        return new Decimal($attributes[$key] - ($value instanceof Decimal ? (string) $value : $value));
    }

    public function serialize($model, $key, $value, $attributes)
    {
        return (string) $value;
    }
}
