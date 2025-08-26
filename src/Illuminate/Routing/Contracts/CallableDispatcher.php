<?php

namespace Illuminate\Routing\Contracts;

use Illuminate\Routing\Route;

interface CallableDispatcher
{
    /**
     * Dispatch a request to a given callable.
     *
     * @param  callable  $callable
     */
    public function dispatch(Route $route, $callable);
}
