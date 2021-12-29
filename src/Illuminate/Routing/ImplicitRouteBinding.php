<?php

namespace Illuminate\Routing;

use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Reflector;
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

        foreach (static::parametersAsClassmap($route) as $parameter => $classname) {
            $parameterValue = $parameters[$parameter];

            if ($parameterValue instanceof UrlRoutable) {
                continue;
            }

            $instance = $container->make($classname);

            $parent = $route->parentOfParameter($parameter);

            $routeBindingMethod = $route->allowsTrashedBindings() && in_array(SoftDeletes::class, class_uses_recursive($instance))
                        ? 'resolveSoftDeletableRouteBinding'
                        : 'resolveRouteBinding';

            if ($parent instanceof UrlRoutable && ($route->enforcesScopedBindings() || array_key_exists($parameter, $route->bindingFields()))) {
                $childRouteBindingMethod = $route->allowsTrashedBindings()
                            ? 'resolveSoftDeletableChildRouteBinding'
                            : 'resolveChildRouteBinding';

                if (! $model = $parent->{$childRouteBindingMethod}(
                    $parameter, $parameterValue, $route->bindingFieldFor($parameter)
                )) {
                    throw (new ModelNotFoundException)->setModel(get_class($instance), [$parameterValue]);
                }
            } elseif (! $model = $instance->{$routeBindingMethod}($parameterValue, $route->bindingFieldFor($parameter))) {
                throw (new ModelNotFoundException)->setModel(get_class($instance), [$parameterValue]);
            }

            $route->setParameter($parameter, $model);
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
     * Guess the class from the parameter name.
     *
     * @param  string $name
     * @return string|null
     */
    protected static function guessClass($name)
    {
        $namespace = app()->getNamespace();
        $classname = Str::studly($name);

        if (class_exists($namespace . $classname)) {
            return $classname;
        }

        if (class_exists($namespace . 'Models\\' . $classname)) {
            return $namespace . 'Models\\' . $classname;
        }

        return null;
    }

    /**
     * Determine the class for the route parameters.
     *
     * @param  \Illuminate\Routing\Route $route
     * @return array
     */
    protected static function parametersAsClassmap($route)
    {
        return $route->parameter('view') ? static::viewParameters($route) : static::signatureParameters($route);
    }

    /**
     * Determine the class name for the route parameters
     * specified in the view's URI.
     *
     * @param  \Illuminate\Routing\Route $route
     * @return array
     */
    protected static function viewParameters($route)
    {
        return collect($route->parameters())
            ->diffKeys($route->defaults)
            ->map(function ($value, $key) {
                return static::guessClass($key);
            })
            ->filter()
            ->all();
    }

    /**
     * Determine the class name for the route parameters
     * based on the signature of the route action.
     *
     * @param  \Illuminate\Routing\Route $route
     * @return array
     */
    protected static function signatureParameters($route)
    {
        return collect($route->signatureParameters(UrlRoutable::class))
            ->mapWithKeys(function ($parameter) use ($route) {
                $name = static::getParameterName($parameter->getName(), $route->parameters());
                if (is_null($name)) {
                    return [];
                }
                return [$name => Reflector::getParameterClassName($parameter)];
            })
            ->filter()
            ->all();
    }

}
