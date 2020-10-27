<?php

namespace Illuminate\Routing;

trait RouteRegexConstraintTrait
{
    /**
     * Apply a route regex requirement to the route.
     *
     * @param  array  $parameters
     * @param  string $regex
     * @return void
     */
    protected function applyRouteRegex(array $parameters, string $regex)
    {
        foreach ($parameters as $parameter) {
            is_string($parameter)
                ? $this->where([$parameter => $regex])
                : $this->applyRouteRegex($parameter, $regex);
        }
    }

    /**
     * Set a number as regular expression requirement on the route.
     *
     * @param  string $parameters
     * @return $this
     */
    public function whereNumber(...$parameters)
    {
        $this->applyRouteRegex($parameters, '[0-9]+');

        return $this;
    }

    /**
     * Set any character between a-z or A-Z as regular expression requirement on the route.
     *
     * @param  string|array $parameter
     * @return $this
     */
    public function whereAlpha(...$parameters)
    {
        $this->applyRouteRegex($parameters, '[a-zA-Z]+');

        return $this;
    }
}
