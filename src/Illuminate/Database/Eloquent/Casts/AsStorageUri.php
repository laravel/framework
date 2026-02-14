<?php

namespace Illuminate\Database\Eloquent\Casts;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\StorageUri;

class AsStorageUri implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return \Illuminate\Contracts\Database\Eloquent\CastsAttributes<\Illuminate\Support\StorageUri, string|StorageUri>
     */
    public static function castUsing(array $arguments)
    {
        return new class implements CastsAttributes
        {
            public function get($model, $key, $value, $attributes)
            {
                return isset($value) ? StorageUri::parse($value) : null;
            }

            public function set($model, $key, $value, $attributes)
            {
                if ($value instanceof StorageUri) {
                    return $value->toUri();
                }

                return isset($value) ? (string) $value : null;
            }
        };
    }
}
