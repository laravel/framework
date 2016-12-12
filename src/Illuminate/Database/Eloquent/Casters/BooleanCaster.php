<?php

namespace Illuminate\Database\Eloquent\Casters;

class BooleanCaster extends AbstractCaster
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
        return (bool) $value;
    }
}
