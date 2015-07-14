<?php

namespace Illuminate\Support;

class Type
{
    /**
     * Cast value to integer.
     *
     * @param  mixed  $value
     * @return int
     */
    public static function castInteger($value)
    {
        return (int) $value;
    }

    /**
     * Cast value to integer.
     *
     * @param  mixed  $value
     * @return int
     */
    public static function castInt($value)
    {
        return self::castInteger($value);
    }

    /**
     * Cast value to float.
     *
     * @param  mixed  $value
     * @return float
     */
    public static function castFloat($value)
    {
        return (float) $value;
    }

    /**
     * Cast value to float.
     *
     * @param  mixed  $value
     * @return float
     */
    public static function castDouble($value)
    {
        return self::castFloat($value);
    }

    /**
     * Cast value to float.
     *
     * @param  mixed  $value
     * @return float
     */
    public static function castReal($value)
    {
        return self::castFloat($value);
    }

    /**
     * Cast value to string.
     *
     * @param  mixed  $value
     * @return string
     */
    public static function castString($value)
    {
        return (string) $value;
    }

    /**
     * Cast value to boolean.
     *
     * @param  mixed  $value
     * @return bool
     */
    public static function castBoolean($value)
    {
        return (bool) $value;
    }

    /**
     * Cast value to boolean.
     *
     * @param  mixed  $value
     * @return bool
     */
    public static function castBool($value)
    {
        return self::castBoolean($value);
    }

    /**
     * Cast value to object.
     *
     * @param  string  $value
     * @param  bool    $assoc
     * @return object
     */
    public static function castObject($value, $assoc = false)
    {
        return json_decode($value, $assoc);
    }

    /**
     * Cast value to array.
     *
     * @param  string  $value
     * @return array
     */
    public static function castArray($value)
    {
        return self::castObject($value, true);
    }

    /**
     * Cast value to array.
     *
     * @param  string  $value
     * @return array
     */
    public static function castJson($value)
    {
        return self::castArray($value);
    }

    /**
     * Cast value to collection.
     *
     * @param  string  $value
     * @return \Illuminate\Support\Collection
     */
    public static function castCollection($value)
    {
        return new Collection(self::castArray($value));
    }
}
