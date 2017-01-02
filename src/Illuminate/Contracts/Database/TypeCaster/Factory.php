<?php

namespace Illuminate\Contracts\Database\TypeCaster;

interface TypeCaster
{
    /**
     * Register a custom Type Caster extension.
     *
     * @param  string  $rule
     * @param  \Closure|string  $fromDatabase
     * @param  \Closure|string|null  $toDatabase
     * @return void
     */
    public function extend($rule, $fromDatabase, $toDatabase = null);
}
