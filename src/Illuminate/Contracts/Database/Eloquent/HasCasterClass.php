<?php

namespace Illuminate\Contracts\Database\Eloquent;

interface HasCasterClass
{
    /**
     * Get the caster class for this class
     *
     * @return string
     */
    public static function getCasterClass();
}
