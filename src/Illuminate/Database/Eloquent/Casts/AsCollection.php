<?php

namespace Illuminate\Database\Eloquent\Casts;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Collection;

class AsCollection implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return object|string
     */
    public static function castUsing(array $arguments)
    {
        return new class implements CastsAttributes {
            public function get($model, $key, $value, $attributes)
            {
                return new Collection(isset($attributes[$key]) ? json_decode($attributes[$key], true) : []);
            }

            public function set($model, $key, $value, $attributes)
            {
                if (is_null($value) || ($value instanceof Collection && $value->isEmpty())) {
                    return [$key => null];
                }

                return [$key => json_encode($value)];
            }
        };
    }
}
