<?php

namespace Illuminate\Support;

use Symfony\Component\VarDumper\Caster\Caster;

class CollectionCaster
{
    /**
     * Get an array representing the properties of a collection.
     *
     * @param  \Illuminate\Support\Collection  $collection
     * @return array
     */
    public static function cast($collection)
    {
        return [
            Caster::PREFIX_VIRTUAL.'all' => $collection->all(),
        ];
    }
}
