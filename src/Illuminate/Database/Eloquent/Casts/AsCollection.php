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
                $items = isset($attributes[$key]) ? json_decode($attributes[$key], true) : [];

                return new Collection($items);
            }

            public function set($model, $key, $value, $attributes)
            {
                if ($this->isEmpty($value) && $this->wasNull($model, $key)) {
                    return [$key => null];
                }

                return [$key => json_encode($value)];
            }

            protected function isEmpty($value)
            {
                if ($value instanceof Collection) {
                    return $value->isEmpty();
                }

                return empty($value);
            }

            protected function wasNull($model, $key)
            {
                return $model->getRawOriginal($key) === null;
            }
        };
    }
}
