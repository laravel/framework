<?php

namespace Illuminate\Contracts\Database\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * Recommendation:
 * Prefer symmetric casts (TGet == TSet) so "$a = $a = $b"
 * remain safe and unsurprising.
 * 
 * @template TGet
 * @template TSet
 */
interface CastsAttributes
{
    /**
     * Transform the attribute from the underlying model values.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array<string, mixed>  $attributes
     * @return TGet|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes);

    /**
     * Transform the attribute to its underlying model values.
     *
     * Important:
     * Implementations must tolerate receiving the runtime cast value (TGet) here.
     * As with object caching, `set()` may receive the value returned by `get()` (TGet).
     * Implementations should therefore accept both TGet and TSet.
     *
     * @param  TGet|TSet|null  $value
     * @return mixed
     */
    public function set(Model $model, string $key, mixed $value, array $attributes);
}
