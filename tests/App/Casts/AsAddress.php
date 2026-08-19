<?php

namespace Illuminate\Tests\App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Tests\App\ValueObjects\AddressDto;

class AsAddress implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        return new AddressDto($attributes['address_line_one'], $attributes['address_line_two']);
    }

    public function set($model, $key, $value, $attributes)
    {
        return ['address_line_one' => $value->lineOne, 'address_line_two' => $value->lineTwo];
    }
}
