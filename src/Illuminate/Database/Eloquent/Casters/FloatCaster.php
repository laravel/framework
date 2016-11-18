<?php

namespace Illuminate\Database\Eloquent\Casters;

class FloatCaster extends AbstractCaster
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
        return (float) $value;
    }
}
