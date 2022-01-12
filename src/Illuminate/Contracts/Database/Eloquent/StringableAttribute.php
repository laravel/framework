<?php

namespace Illuminate\Contracts\Database\Eloquent;

interface StringableAttribute
{
    /**
     * Allows enums to be used as morthed entity type
     * @return string
     */
    public function toString(): string;
}
