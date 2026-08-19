<?php

namespace Illuminate\Tests\App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Tests\App\ValueObjects\AddressCastValue;

class AddressCaster implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        if (is_null($attributes['address_line_one'])) {
            return;
        }

        return new AddressCastValue($attributes['address_line_one'], $attributes['address_line_two']);
    }

    public function set($model, $key, $value, $attributes)
    {
        if (is_null($value)) {
            return [
                'address_line_one' => null,
                'address_line_two' => null,
            ];
        }

        return ['address_line_one' => $value->lineOne, 'address_line_two' => $value->lineTwo];
    }
}
