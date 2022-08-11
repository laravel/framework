<?php

namespace Illuminate\Routing\Exceptions;

use Exception;
use Illuminate\Routing\Route;

class RouteAlreadyRegisteredException extends Exception
{
    /**
     * @param  \Illuminate\Routing\Route  $route
     * @return static
     */
    public static function forRoute(Route $route)
    {
        $fullUri = $route->getDomain()
            ? $route->getDomain().'/'.$route->uri()
            : $route->uri();

        $message = sprintf('Route [%s] has already been registered.', $fullUri);

        return new static($message);
    }
}
