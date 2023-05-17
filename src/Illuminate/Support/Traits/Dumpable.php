<?php

namespace Illuminate\Support\Traits;

trait Dumpable
{
    /**
     * Dump the instance and end the script.
     *
     * @param  mixed  ...$args
     * @return never
     */
    public function dd(...$args)
    {
        $this->dump(...$args);

        dd();
    }

    /**
     * Dump the instance.
     *
     * @param  mixed  ...$args
     * @return $this
     */
    public function dump(...$args)
    {
        dump($this, ...$args);

        return $this;
    }
}
