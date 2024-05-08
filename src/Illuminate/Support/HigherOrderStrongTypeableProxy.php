<?php

namespace Illuminate\Support;

class HigherOrderStrongTypeableProxy
{
    /**
     * Create a new strong-typeable proxy instance.
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
     * @return \Illuminate\Support\StrongTypeable
     */
    public function __get(string $key): StrongTypeable
    {
        return new StrongTypeable($this->target, $key);
    }
}
