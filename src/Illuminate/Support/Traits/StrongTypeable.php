<?php

namespace Illuminate\Support\Traits;

use Illuminate\Support\HigherOrderStrongTypeableProxy;

trait StrongTypeable
{
    /**
     * Retrieve a higher order strong-typeable proxy.
     *
     * @return HigherOrderStrongTypeableProxy
     */
    public function typed(): HigherOrderStrongTypeableProxy
    {
        return new HigherOrderStrongTypeableProxy($this);
    }
}
