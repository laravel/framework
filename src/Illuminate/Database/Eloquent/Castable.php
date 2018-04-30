<?php

namespace Illuminate\Database\Eloquent;

interface Castable
{
    /**
     * Create your object from DB value.
     *
     * @param $value
     * @return mixed
     */
    public static function fromModelValue($value);
}
