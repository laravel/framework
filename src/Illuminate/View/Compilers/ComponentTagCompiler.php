<?php

namespace Illuminate\View\Compilers;

use Illuminate\Support\Str;

/**
 * @author Spatie bvba <info@spatie.be>
 * @author Taylor Otwell <taylor@laravel.com>
 */
class ComponentTagCompiler
{
    /**
     * Compile the slot tags within the given string.
     *
     * @param  string  $value
     * @return string
     */
    public static function compileSlots(string $value)
    {
        $value = preg_replace_callback('/<\s*slot\s+name=(?<name>(\"[^\"]+\"|\\\'[^\\\']+\\\'|[^\s>]+))\s*>/', function ($matches) {
            return " @slot('".static::stripQuotes($matches['name'])."') ";
        }, $value);

        return preg_replace('/<\/\s*slot[^>]*>/', ' @endslot', $value);
    }

    /**
     * Strip any quotes from the given string.
     */
    public static function stripQuotes(string $value)
    {
        return Str::startsWith($value, ['"', '\''])
                    ? substr($value, 1, -1)
                    : $value;
    }
}
