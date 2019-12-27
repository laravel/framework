<?php

namespace Illuminate\Contracts\Database\Eloquent;

interface Castable
{
    /**
     * Get the mutated value.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function handle($value = null);
}
