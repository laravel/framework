<?php

namespace Illuminate\Database\Eloquent;

interface ObjectCastable
{
    /**
     * Returns an instance of the implementing class from the original object
     *
     * @param \stdClass  $object
     * @return mixed
     */
    public static function castFromObject(\stdClass $object);
}