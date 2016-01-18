<?php

namespace Illuminate\Routing;

use RuntimeException;

class RoutingException extends RuntimeException
{
    protected $route;

    public function setRoute(Route $route)
    {
        $this->route = $route;
        $this->message .= " Failed for route ".$route->uri();

        return $this;
    }
}