<?php

namespace Illuminate\Contracts\Database\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * @template TGet
 * @template TSet
 */
interface CastsAttributes
{
    /**
     * Transform the attribute from the underlying model values.
     *
     * @param  array<string, mixed>  $attributes
     * @return TGet|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes);

    /**
     * Transform the attribute to its underlying model values.
     *
     * @param  TSet|null  $value
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes);
}
