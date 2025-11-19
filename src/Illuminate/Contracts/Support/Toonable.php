<?php

namespace Illuminate\Contracts\Support;

interface Toonable
{
    /**
     * Convert the object to its TOON representation.
     *
     * @return string
     */
    public function toToon(): string;
}
