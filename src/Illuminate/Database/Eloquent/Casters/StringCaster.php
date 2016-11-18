<?php

namespace Illuminate\Database\Eloquent\Casters;

class StringCaster extends AbstractCaster
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
        return (string) $value;
    }
}
