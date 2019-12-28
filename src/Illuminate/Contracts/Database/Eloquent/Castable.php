<?php

namespace Illuminate\Contracts\Database\Eloquent;

interface Castable
{
    /**
     * Get a given attribute from the model.
     *
     * @param  mixed  $value
     * @return mixed
     */
    public function get($value = null);

    /**
     * Set a given attribute on the model.
     *
     * @param  mixed  $value
     * @return mixed
     */
    public function set($value = null);
}
