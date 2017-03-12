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
            $class = $parameter->getClass();

            if (! $route->parameter($parameter->name) instanceof Model) {
                $model = $container->make($class->name);

                $parameterName = array_key_exists($parameter->name, $parameters) ? $parameter->name : null;

                // check if parameter name used was camelized in routed callback method
                if (!$parameterName) {
                    $snakeParamName = snake_case($parameter->name);
                    $parameterName = array_key_exists($snakeParamName, $parameters) ? $snakeParamName : null;
                }

                if ($parameterName) {
                    $value = $model->where($model->getRouteKeyName(), $parameters[$parameterName])->firstOrFail();
                    $route->setParameter($parameterName, $value);
                }
            }
        }
    }
}
