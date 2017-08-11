<?php

namespace Illuminate\Routing;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Arr;

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

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function addRoute(Route $route) {
        $this->routes->add($route);

        return $this;
    }
}
