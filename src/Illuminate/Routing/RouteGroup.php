<?php

namespace Illuminate\Routing;

use Illuminate\Contracts\Support\Arrayable;

class RouteGroup implements Arrayable
{
    protected $attributes = [];
    protected $routes;

    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->routes = new RouteCollection;
    }

    /**
     * Get the attributes as a plain array.
     *
     * @return array
     */
    public function toArray()
    {
        return array_map(function ($value) {
            return $value instanceof Arrayable ? $value->toArray() : $value;
        }, $this->attributes);
    }

    /**
     * Get the attributes.
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Add a route to the group's RouteCollection.
     *
     * @param \Illuminate\Routing\Route $route
     * @return \Illuminate\Routing\RouteGroup
     */
    public function addRoute(Route $route)
    {
        $this->routes->add($route);

        return $this;
    }
}
