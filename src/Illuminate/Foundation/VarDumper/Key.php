<?php

namespace Illuminate\Foundation\VarDumper;

use Symfony\Component\VarDumper\Caster\Caster;

class Key
{
    /**
     * Format a key as having protected visibility.
     *
     * @param  string  $key
     * @return string
     */
    public static function protected($key)
    {
        return Caster::PREFIX_PROTECTED.$key;
    }

    /**
     * Format a key as being virtual.
     *
     * @param  string  $key
     * @return string
     */
    public static function virtual($key)
    {
        return Caster::PREFIX_VIRTUAL.$key;
    }

    /**
     * Format a key as being dynamic.
     *
     * @param  string  $key
     * @return string
     */
    public static function dynamic($key)
    {
        return Caster::PREFIX_DYNAMIC.$key;
    }
}
