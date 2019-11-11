<?php

namespace Illuminate\Routing;

use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

class ImplicitRouteBinding
{
    /**
     * Resolve the implicit route bindings for the given route.
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  \Illuminate\Routing\Route  $route
     * @return void
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public static function resolveForRoute($container, $route)
    {
        $parameters = $route->parameters();
        $signatureParameters = $route->signatureParameters(UrlRoutable::class);

        foreach ($signatureParameters as $parameter) {
            if (! $parameterName = static::getParameterName($parameter->name, $parameters)) {
                if (! static::isNestedParameter($parameter->name, $parameters)) {
                    continue;
                }

                $parameterName = static::getNestedParameterName($parameter->name, $parameters);
            }

            $parameterValue = $parameters[$parameterName];

            if ($parameterValue instanceof UrlRoutable) {
                continue;
            }

            $instance = $container->make($parameter->getClass()->name);

            if (count($chunks = explode('__from__', $parameterName)) > 1) {
                $parentName = $chunks[1];
                $parentParameter = $route->parameters()[$parentName] ?? $parentName;

                $model = $instance->where($instance->getRouteKeyName(), $parameterValue)
                    ->whereHas($parentName, function ($query) use ($parentParameter) {
                        $query->where($parentParameter->getRouteKeyName(), $parentParameter->getRouteKey());
                    })
                    ->first();
            } elseif (! $model = $instance->resolveRouteBinding($parameterValue)) {
                throw (new ModelNotFoundException)->setModel(get_class($instance), [$parameterValue]);
            }

            $route->setParameter($parameterName, $model);
        }
    }

    /**
     * Return the parameter name if it exists in the given parameters.
     *
     * @param  string  $name
     * @param  array  $parameters
     * @return string|null
     */
    protected static function getParameterName($name, $parameters)
    {
        if (array_key_exists($name, $parameters)) {
            return $name;
        }

        if (array_key_exists($snakedName = Str::snake($name), $parameters)) {
            return $snakedName;
        }
    }

    /**
     * Check if parameter is nested.
     *
     * @param string $name
     * @return bool
     */
    protected static function isNestedParameter($name, $parameters)
    {
        return count(static::findNestedParameter(...func_get_args()));
    }

    /**
     * Get nested parameter name.
     *
     * @param string $name
     * @param array $parameters
     * @return string
     */
    protected static function getNestedParameterName($name, $parameters)
    {
        $nestedName = key(static::findNestedParameter(...func_get_args()));

        return $nestedName;
    }

    /**
     * Find parameter.
     *
     * @param string $name
     * @param array $parameters
     * @return array
     */
    protected static function findNestedParameter($name, $parameters)
    {
        return array_filter($parameters, function ($parameterName) use ($name) {
            return strpos($parameterName, $name.'__from__') !== false;
        }, ARRAY_FILTER_USE_KEY);
    }
}
