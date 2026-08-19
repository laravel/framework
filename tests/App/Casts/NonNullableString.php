<?php

namespace Illuminate\Tests\App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class NonNullableString implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  string  $value
     * @param  array  $attributes
     * @return string|null
     */
    public function get($model, $key, $value, $attributes)
    {
        return ($value != '') ? $value : null;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  string|null  $value
     * @param  array  $attributes
     * @return string
     */
    public function set($model, $key, $value, $attributes)
    {
        return $value ?? '';
    }
}
