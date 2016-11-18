<?php

namespace Illuminate\Database\Eloquent\Casters;

class ObjectCaster extends AbstractCaster
{
    /**
     * {@inheritdoc}
     */
    public function as($value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function from($value)
    {
        return json_decode($value, true);
    }
}
