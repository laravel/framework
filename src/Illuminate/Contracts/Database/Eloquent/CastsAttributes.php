<?php

namespace Illuminate\Contracts\Database\Eloquent;

use Illuminate\Database\Eloquent\Model;

interface CastsAttributes
{
    /**
     * Transform the attribute from the underlying model values.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes);

    /**
     * Transform the attribute to its underlying model values.
     *
     * @return mixed
     */
    public function set(Model $model, string $key, mixed $value, array $attributes);
}
