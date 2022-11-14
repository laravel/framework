<?php

namespace Illuminate\Database\Eloquent\Casts;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class AsObject implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return \Illuminate\Contracts\Database\Eloquent\CastsAttributes
     */
    public static function castUsing(array $arguments)
    {
        return new class implements CastsAttributes
        {
            public function get($model, $key, $value, $attributes)
            {
                if (! isset($attributes[$key])) {
                    return;
                }

                $data = json_decode($attributes[$key], false);

                return (object) $data;
            }

            public function set($model, $key, $value, $attributes)
            {
                return [$key => json_encode($value, JSON_FORCE_OBJECT)];
            }

            public function serialize($model, string $key, $value, array $attributes)
            {
                return $value;
            }
        };
    }
}
