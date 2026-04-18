<?php

namespace Illuminate\Log\Formatters;

class ExceptionContextState
{
    /**
     * @var \WeakMap<\Throwable, true>
     */
    protected static \WeakMap $map;

    public static function reportContextBuilt(\Throwable $e): void
    {
        static::$map[$e] = true;
    }

    public static function hasBuiltContextFor(\Throwable $e): bool
    {
        return isset(static::$map[$e]);
    }
}
