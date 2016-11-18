<?php

namespace Illuminate\Database\Eloquent\Casters;

class ArrayCaster extends AbstractCaster
{
    /**
     * Encode the given value as JSON.
     *
     * @param mixed $value
     *
     * @return string
     */
    public function as($value)
    {
        return json_encode($value);
    }

    /**
     * Decode the given JSON back into an array or object.
     *
     * @param string $value
     *
     * @return mixed
     */
    public function from($value)
    {
        return json_decode($value, $this->options->asObject);
    }
}
