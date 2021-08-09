<?php

namespace Illuminate\Container;

abstract class TypeDeclarationsEnum
{
    private const array = [];
    private const callable = null;
    private const bool = false;
    private const float = 0.0;
    private const int = 0;
    private const string = '';
    private const iterable = [];
    private const object = null;
    private const mixed = null;

    /**
     * Get default value of parameter type.
     *
     * @param string $name
     * @return false|mixed|null
     */
    public static function default(string $name)
    {
        $constants = new \ReflectionClass(self::class);

        return $constants->hasConstant($name)
            ? $constants->getConstant($name)
            : null;
    }
}
