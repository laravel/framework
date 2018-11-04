<?php

namespace Illuminate\Support;

use Carbon\Carbon as BaseCarbon;

class Carbon extends BaseCarbon
{
    /**
     * Return protected value $serializer.
     *
     * @return callable|null
     */
    public static function getSerializer()
    {
        return static::$serializer;
    }

    /**
     * Return a serialized string of the instance.
     *
     * @return string
     */
    public function serialize()
    {
        if (static::$serializer) {
            return call_user_func(static::$serializer, $this);
        }

        return serialize($this);
    }
}
