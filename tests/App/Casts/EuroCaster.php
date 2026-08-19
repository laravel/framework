<?php

namespace Illuminate\Tests\App\Casts;

use Brick\Math\BigNumber;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Tests\App\ValueObjects\Euro;

class EuroCaster implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        return new Euro($value);
    }

    public function set($model, $key, $value, $attributes)
    {
        return $value instanceof Euro ? $value->value : $value;
    }

    public function increment($model, $key, $value, $attributes)
    {
        $model->$key = new Euro((string) BigNumber::of((string) $model->$key->value)->plus($value->value)->toScale(2));

        return $model->$key;
    }

    public function decrement($model, $key, $value, $attributes)
    {
        $model->$key = new Euro((string) BigNumber::of((string) $model->$key->value)->subtract($value->value)->toScale(2));

        return $model->$key;
    }
}
