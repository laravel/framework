<?php

namespace Illuminate\Contracts\Auth;

interface HasRequestThrottleLimit
{
    /**
     * Get the user request limit.
     *
     * @return int
     */
    public function getRequestLimit();
}
