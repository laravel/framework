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
        $routeParameters = $route->generatedParameters();

        foreach ($route->signatureParameters(Model::class) as $parameter) {
            $class = $parameter->getClass();

            foreach ($routeParameters as $routeParameter) {
                if ($routeParameter->name() == $parameter->name &&
                    !$route->parameter($parameter->name) instanceof Model
                ) {
                    $model = $container->make($class->name);

                    $route->setParameter(
                        $routeParameter->parameter(),
                        $model->where($routeParameter->key() ?: $model->getRouteKeyName(), $routeParameter->value())->firstOrFail()
                    );
                }
            }
        }
    }
}
