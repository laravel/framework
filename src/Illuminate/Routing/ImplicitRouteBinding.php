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
        $parameters = [];

        foreach ($route->parameters() as $parameter => $value) {
            $parts = explode(':', $parameter);
            $name = $parts[0];
            $key = $parts[1] ?? null;
            $parameters[$name] = ['name' => $parameter] + compact('key', 'value');
        }

        foreach ($route->signatureParameters(Model::class) as $parameter) {
            $class = $parameter->getClass();

            if (array_key_exists($parameter->name, $parameters) &&
                ! $route->parameter($parameter->name) instanceof Model
            ) {
                $method = $parameter->isDefaultValueAvailable() ? 'first' : 'firstOrFail';

                $model = $container->make($class->name);

                $route->setParameter(
                    $parameters[$parameter->name]['name'], $model->where(
                    $parameters[$parameter->name]['key'] ?? $model->getRouteKeyName(),
                    $parameters[$parameter->name]['value']
                )->{$method}()
                );
            }
        }
    }
}
