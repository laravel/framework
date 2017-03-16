<?php

namespace Illuminate\Routing;

use Illuminate\Database\Eloquent\Model;

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

        foreach ($route->signatureParameters(Model::class) as $parameter) {
            if ($route->parameter($parameter->name) instanceof Model) {
                continue;
            }

            $model = $container->make($parameter->getClass()->name);

            $parameterName = static::checkForParameter($parameter->name, $parameters) ?:
                             static::checkForParameter(snake_case($parameter->name), $parameters);

            if ($parameterName) {
                $route->setParameter($parameterName, $model->where(
                    $model->getRouteKeyName(), $parameters[$parameterName]
                )->firstOrFail());
            }
        }
    }

    /**
     * Return the parameter name if it exists in the given parameters.
     *
     * @param  string  $name
     * @param  array  $parameters
     * @return string|null
     */
    protected static function checkForParameter($name, $parameters)
    {
        return array_key_exists($name, $parameters)
                        ? $name : null;
    }
}
