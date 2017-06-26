<?php

namespace Illuminate\Contracts\Support;

interface Macro
{
    /**
     * Return the callable macro.
     *
     * @return  callable
     */
    public function handle();
}
