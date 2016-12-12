<?php

namespace Illuminate\Database\Eloquent\Casters;

class EncryptedCaster extends AbstractCaster
{
    /**
     * {@inheritdoc}
     */
    public function as($value)
    {
        return encrypt($value);
    }

    /**
     * {@inheritdoc}
     */
    public function from($value)
    {
        return decrypt($value);
    }
}
