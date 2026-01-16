<?php

namespace Illuminate\Contracts\Validation;

/**
 * @template TValue
 */
interface CastsValidatedValue
{
    /**
     * Cast the validated value.
     *
     * @param  mixed  $value
     * @param  string  $key
     * @param  array<string, mixed>  $attributes
     * @return TValue|null
     */
    public function cast(mixed $value, string $key, array $attributes);
}
