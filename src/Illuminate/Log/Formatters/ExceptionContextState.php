<?php

namespace Illuminate\Log\Formatters;

use WeakMap;

class ExceptionContextState
{
    /**
     * @var \WeakMap<\Throwable, true>
     */
    protected static \WeakMap $map;

    public static function reportContextBuilt(\Throwable $e): void
    {
        static::$map ??= new \WeakMap();

        static::$map[$e] = true;
    }

    public static function hasBuiltContextFor(\Throwable $e): bool
    {
        static::$map ??= new \WeakMap();

        return isset(static::$map[$e]);
    }
}
