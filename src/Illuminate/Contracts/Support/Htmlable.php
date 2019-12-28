<?php

namespace Illuminate\Contracts\Support;

interface Htmlable
{
    /**
     * Get the HTML string.
     *
     * @return string
     */
    public function toHtml();

    /**
     * Get the HTML string.
     *
     * @return string
     */
    public function __toString();
}
