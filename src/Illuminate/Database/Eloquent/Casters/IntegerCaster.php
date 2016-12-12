<?php

namespace Illuminate\Database\Eloquent\Casters;

class IntegerCaster extends AbstractCaster
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
        return (int) $value;
    }
}
