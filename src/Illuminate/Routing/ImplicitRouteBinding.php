<?php

namespace Illuminate\Routing;

use Illuminate\Support\Str;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ImplicitRouteBinding
{
    /**
     * Resolve the implicit route bindings for the given route.
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  \Illuminate\Routing\Route  $route
     * @return void
     */
    public static function resolveForRoute($container, $route)
    {
        $parameters = $route->parameters();

        foreach ($signatureParameters = $route->signatureParameters(UrlRoutable::class) as $index => $parameter) {
            if (! $parameterName = static::getParameterName($parameter->name, $parameters)) {
                continue;
            }

            $nestedBinding = NestedBinding::setRelationshipForImplicitBinding($route, $signatureParameters, $index);

            $parameterValue = $parameters[$parameterName];

            if ($parameterValue instanceof UrlRoutable) {
                continue;
            }

            $instance = $container->make($parameter->getClass()->name);

            if (! $model = $instance->resolveRouteBinding($parameterValue, $nestedBinding)) {
                throw (new ModelNotFoundException)->setModel(get_class($instance));
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
}
