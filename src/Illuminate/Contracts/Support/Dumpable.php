<?php

namespace Illuminate\Contracts\Support;

interface Dumpable
{
    /**
     * Dump the object and end the script.
     *
     * @return never
     */
    public function dd();

    /**
     * Dump the object.
     *
     * @return mixed
     */
    public function dump();
}
