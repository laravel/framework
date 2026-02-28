<?php

declare(strict_types=1);

namespace Illuminate\Support;

class MacroOverrides
{
    protected static $preventMacroOverrides = false;

    public static function prevent($prevent = true)
    {
        static::$preventMacroOverrides = $prevent;
    }

    public static function shouldPrevent(): bool
    {
        return static::$preventMacroOverrides;
    }
}
