<?php

namespace Illuminate\Contracts\Validation;

interface ValidationCastable
{
    /**
     * Get the caster class to use when casting validated values.
     *
     * @param  list<string>  $arguments
     * @return \Illuminate\Contracts\Validation\CastsValidatedValue|class-string<\Illuminate\Contracts\Validation\CastsValidatedValue>
     */
    public static function castUsing(array $arguments);
}
