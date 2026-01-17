<?php

namespace Illuminate\Contracts\Support;

/**
 * @template TValue
 */
interface CastsValue
{
    /**
     * Cast the given value.
     *
     * @param  mixed  $value
     * @param  string  $key
     * @param  array<string, mixed>  $attributes
     * @return TValue|null
     */
    public function cast(mixed $value, string $key, array $attributes);
}
