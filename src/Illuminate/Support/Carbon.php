<?php

namespace Illuminate\Support;

use JsonSerializable;
use Carbon\Carbon as BaseCarbon;
use Illuminate\Support\Traits\Macroable;

class Carbon extends BaseCarbon implements JsonSerializable
{
    use Macroable;

    /**
     * The custom Carbon JSON serializer.
     *
     * @var callable|null
     */
    protected static $serializer;

     /**
     * Create a new Carbon instance.
     *
     * @param string|int|null           $time
     * @param \DateTimeZone|string|null $tz
     */
    public function __construct($time = null, $tz = null)
    {
        // Convert timestamp to date string
        if (is_int($time)) {
            $time = date('Y-m-d H:i:s', $time);
        }

        parent::__construct($time, $tz);
    }

    /**
     * Prepare the object for JSON serialization.
     *
     * @return array|string
     */
    public function jsonSerialize()
    {
        if (static::$serializer) {
            return call_user_func(static::$serializer, $this);
        }

        $carbon = $this;

        return call_user_func(function () use ($carbon) {
            return get_object_vars($carbon);
        });
    }

    /**
     * JSON serialize all Carbon instances using the given callback.
     *
     * @param  callable  $callback
     * @return void
     */
    public static function serializeUsing($callback)
    {
        static::$serializer = $callback;
    }

    /**
     * Create a new Carbon instance based on the given state array.
     *
     * @param  array  $array
     * @return static
     */
    public static function __set_state($array)
    {
        return static::instance(parent::__set_state($array));
    }
}
