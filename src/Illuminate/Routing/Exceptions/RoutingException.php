<?php

namespace Illuminate\Routing\Exceptions;

use RuntimeException;
use Illuminate\Routing\Route;

class RoutingException extends RuntimeException
{
    /**
     * The Route object related to the caused error.
     *
     * @var \Illuminate\Routing\Route
     */
    protected $route;

    /**
     * Set the Route related to the error.
     *
     * @param  \Illuminate\Routing\Route $route
     * @return $this
     */
    public function setRoute(Route $route)
    {
        $this->route = $route;
        $this->message .= ' Failed for route '.$route->uri().'.';

        return $this;
    }
}
