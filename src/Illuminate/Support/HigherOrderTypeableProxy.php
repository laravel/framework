<?php

namespace Illuminate\Support;

class HigherOrderTypeableProxy
{
    /**
     * Create a new typeable proxy instance.
     *
     * @param  mixed  $target
     * @return void
     */
    public function __construct(protected mixed $target)
    {
        //
    }

    /**
     * Dynamically access properties from the target.
     *
     * @param  string  $key
     * @return \Illuminate\Support\Typeable
     */
    public function __get(string $key): Typeable
    {
        return new Typeable($this->target, $key);
    }
}
