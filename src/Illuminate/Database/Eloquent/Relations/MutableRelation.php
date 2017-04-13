<?php

namespace Illuminate\Database\Eloquent\Relations;

interface MutableRelation
{
    /**
     * Set the the relationship value.
     *
     * @param $value
     *
     * @return void
     */
    public function setValue($value);
}
