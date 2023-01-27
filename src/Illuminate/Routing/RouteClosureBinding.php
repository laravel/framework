<?php

namespace Illuminate\Routing;

class RouteClosureBinding
{
    /**
     * The name of the route parameter.
     *
     * @var string
     */
    protected $name;

    /**
     * The callback to execute to obtain the route parameter.
     *
     * @var \Closure
     */
    protected $callback;

    /**
     * @param  string  $name
     * @param  \Closure  $callback
     */
    public function __construct($name, callable $callback)
    {
        $this->name = $name;
        $this->callback = $callback;
    }

    public function resolveForRoute(Route $route)
    {
        $route->setParameter($this->name, ($this->callback)());
    }
}
