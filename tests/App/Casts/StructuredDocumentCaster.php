<?php

namespace Illuminate\Tests\App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\ComparesCastableAttributes;

class StructuredDocumentCaster implements CastsAttributes, ComparesCastableAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        return json_decode($value);
    }

    public function set($model, $key, $value, $attributes)
    {
        return json_encode($value);
    }

    public function compare($model, $key, $value1, $value2)
    {
        return json_decode($value1) == json_decode($value2);
    }
}
