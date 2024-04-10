<?php

namespace Illuminate\Routing\Controllers;

interface HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     *
     * @return \Illuminate\Routing\Controllers\Middleware[]
     */
    public static function middleware();
}
