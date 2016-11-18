<?php

namespace Illuminate\Database\Eloquent\Casters;

use Illuminate\Support\Collection;

class CollectionCaster extends AbstractCaster
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
        return new Collection(json_decode($value));
    }
}
