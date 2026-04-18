<?php

namespace Illuminate\Log\Formatters;

use WeakMap;
use Throwable;

class ExceptionContextState
{
    /**
     * The exceptions which have had their context built.
     *
     * @var \WeakMap<\Throwable, true>
     */
    protected static WeakMap $map;

    /**
     * Record that we have already built context for the Throwable.
     */
    public static function reportContextBuilt(Throwable $e): void
    {
        static::getMap()[$e] = true;
    }

    /**
     * Determine if context has already been built for the exception.
     */
    public static function hasBuiltContextFor(Throwable $e): bool
    {
        return isset(static::getMap()[$e]);
    }

    /**
     * Get the underlying map of exceptions for which context has been built.
     *
     * @return WeakMap<\Throwable, true>
     */
    public static function getMap(): WeakMap
    {
        return self::$map ??= new WeakMap();
    }
}
