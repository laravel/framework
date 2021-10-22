<?php

namespace Illuminate\Contracts\Support;

interface CanBeEscapedWhenConvertedToString
{
    /**
     * Indicate that the object's string representation should be escaped when __toString is invoked.
     *
     * @param  bool  $escape
     * @return $this
     */
    public function escapeWhenConvertingToString($escape = true);
}
