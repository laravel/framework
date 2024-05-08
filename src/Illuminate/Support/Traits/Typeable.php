<?php

namespace Illuminate\Support\Traits;

use Illuminate\Support\HigherOrderTypeableProxy;

trait Typeable
{
    /**
     * Retrieve a higher order typeable proxy.
     *
     * @return HigherOrderTypeableProxy
     */
    public function typed(): HigherOrderTypeableProxy
    {
        return new HigherOrderTypeableProxy($this);
    }
}
