<?php

declare(strict_types=1);

namespace Illuminate\Support;

class MacroOverrides
{
    /**
     * Indicates if a macros should not be overridden when it already exists.
     *
     * @var bool
     */
    protected static $preventMacroOverrides = false;

    /**
     * Prevents macro overrides.
     *
     * @param  bool  $prevent
     * @return void
     */
    public static function prevent($prevent = true): void
    {
        static::$preventMacroOverrides = $prevent;
    }

    /**
     * Determines if macro overrides should be prevented.
     *
     * @return bool
     */
    public static function shouldPrevent(): bool
    {
        return static::$preventMacroOverrides;
    }
}
