<?php

namespace Illuminate\Contracts\Database\Eloquent;

interface Castable
{
    /**
     * Get a given attribute from the model.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    public function fromDatabase($key, $value = null);

    /**
     * Set a given attribute on the model.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    public function toDatabase($key, $value = null);
}
