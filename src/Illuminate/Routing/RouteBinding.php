<?php

namespace Illuminate\Routing;

use BadMethodCallException;
use Closure;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

class RouteBinding
{
    /**
     * Create a Route model binding for a given callback.
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  \Closure|string  $binder
     * @return \Closure
     */
    public static function forCallback($container, $binder)
    {
        if (is_string($binder)) {
            return static::createClassBinding($container, $binder);
        }

        return $binder;
    }

    /**
     * Create a class based binding using the IoC container.
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  string  $binding
     * @return \Closure
     */
    protected static function createClassBinding($container, $binding)
    {
        return function ($value, $route) use ($container, $binding) {
            // If the binding has an @ sign, we will assume it's being used to delimit
            // the class name from the bind method name. This allows for bindings
            // to run multiple bind methods in a single class for convenience.
            [$class, $method] = Str::parseCallback($binding, 'bind');

            $callable = [$container->make($class), $method];

            return $callable($value, $route);
        };
    }

    /**
     * Create a Route model binding for a model.
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  string  $class
     * @param  \Closure|null  $callback
     * @param  string|null  $key
     * @return \Closure
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public static function forModel($container, $class, $callback = null, $key = null)
    {
        return function ($value, $route = null, $field = null) use ($container, $class, $callback, $key) {
            if (is_null($value) || is_null($key)) {
                return;
            }

            // For model binders, we will attempt to retrieve the models using the first
            // method on the model instance. If we cannot retrieve the models we'll
            // throw a not found exception otherwise we will return the instance.
            $instance = $container->make($class);

            $parent = $route->parentOfParameter($key);

            if ($parent instanceof UrlRoutable && in_array($key, array_keys($route->bindingFields()))) {
                try {
                    $model = $parent->resolveChildRouteBinding(
                        $key, $value, $field
                    );
                } catch (BadMethodCallException $exception) {
                    $model = $parent->resolveChildRouteBinding(
                        class_basename($class), $value, $field
                    );
                }
            } else {
                $model = $instance->resolveRouteBinding($value, $field);
            }

            if ($model) {
                return $model;
            }

            // If a callback was supplied to the method we will call that to determine
            // what we should do when the model is not found. This just gives these
            // developer a little greater flexibility to decide what will happen.
            if ($callback instanceof Closure) {
                return $callback($value);
            }

            throw (new ModelNotFoundException)->setModel($class);
        };
    }
}
