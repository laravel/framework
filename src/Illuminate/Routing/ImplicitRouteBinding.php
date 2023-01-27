<?php

namespace Illuminate\Routing;

use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Routing\Exceptions\BackedEnumCaseNotFoundException;
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
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException<\Illuminate\Database\Eloquent\Model>
     * @throws \Illuminate\Routing\Exceptions\BackedEnumCaseNotFoundException
     */
    public static function resolveForRoute($container, $route)
    {
        $parameters = $route->parameters();

        static::resolveBackedEnumsForRoute($route, $parameters);

        static::resolveBindingsForRoute($container, $route, $parameters);
    }

    /**
     * Resolve implicit route bindings for Eloquent models that require a lock.
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  \Illuminate\Routing\Route  $route
     * @return void
     */
    public static function resolveClosureBindingsForRoute($container, $route)
    {
        foreach (array_filter($route->parameters(), fn ($value) => $value instanceof RouteClosureBinding) as $binding) {
            $binding->resolveForRoute($route);
        }
    }

    /**
     * @param  \Illuminate\Container\Container  $container
     * @param  \Illuminate\Routing\Route  $route
     * @param  array|null  $parameters
     * @return void
     */
    protected static function resolveBindingsForRoute($container, $route, $parameters)
    {
        foreach ($route->signatureParameters(['subClass' => UrlRoutable::class]) as $parameter) {
            if (! $parameterName = static::getParameterName($parameter->getName(), $parameters)) {
                continue;
            }

            $parameterValue = $parameters[$parameterName];

            if ($parameterValue instanceof UrlRoutable) {
                continue;
            }

            $instance = $container->make(Reflector::getParameterClassName($parameter));

            $parent = $route->parentOfParameter($parameterName);

            if ($parent instanceof UrlRoutable &&
                ! $route->preventsScopedBindings() &&
                ($route->enforcesScopedBindings() || array_key_exists($parameterName, $route->bindingFields()))) {
                $model = static::resolveChildRouteBinding($route, $instance, $parent, $parameterName, $parameterValue);
            } else {
                $model = static::resolveRouteBinding($route, $instance, $parameterName, $parameterValue);
            }

            $route->setParameter($parameterName, $model);
        }
    }

    /**
     * @param  \Illuminate\Routing\Route  $route
     * @param  mixed  $instance
     * @param  string  $parameterName
     * @param  mixed  $parameterValue
     * @return RouteClosureBinding|Model
     */
    protected static function resolveRouteBinding($route, $instance, $parameterName, $parameterValue)
    {
        if ($route->allowsTrashedBindings() && in_array(SoftDeletes::class, class_uses_recursive($instance))) {
            $instance->resolveWithTrashed = true;
        }

        if ($route->requiresLockBindings()) {
            $instance->resolveWithLock = true;

            return new RouteClosureBinding($parameterName, fn () => $instance->resolveRouteBinding(
                $parameterValue, $route->bindingFieldFor($parameterName)
            ));
        }

        if (! $model = $instance->resolveRouteBinding($parameterValue, $route->bindingFieldFor($parameterName))) {
            throw (new ModelNotFoundException)->setModel(get_class($instance), [$parameterValue]);
        }

        return $model;
    }

    /**
     * @param  \Illuminate\Routing\Route  $route
     * @param  mixed  $instance
     * @param  mixed  $parent
     * @param  string  $parameterName
     * @param  mixed  $parameterValue
     * @return RouteClosureBinding|Model
     */
    protected static function resolveChildRouteBinding($route, $instance, $parent, $parameterName, $parameterValue)
    {
        if ($route->allowsTrashedBindings() && in_array(SoftDeletes::class, class_uses_recursive($instance))) {
            $instance->resolveWithTrashed = true;
        }

        if ($route->requiresLockBindings()) {
            $instance->resolveWithLock = true;

            return new RouteClosureBinding($parameterName, fn () => $parent->resolveChildRouteBinding(
                $parameterName, $parameterValue, $route->bindingFieldFor($parameterName)
            ));
        }

        if (! $model = $parent->resolveChildRouteBinding(
            $parameterName, $parameterValue, $route->bindingFieldFor($parameterName)
        )) {
            throw (new ModelNotFoundException)->setModel(get_class($instance), [$parameterValue]);
        }

        return $model;
    }

    /**
     * Resolve the Backed Enums route bindings for the route.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  array  $parameters
     * @return void
     *
     * @throws \Illuminate\Routing\Exceptions\BackedEnumCaseNotFoundException
     */
    protected static function resolveBackedEnumsForRoute($route, $parameters)
    {
        foreach ($route->signatureParameters(['backedEnum' => true]) as $parameter) {
            if (! $parameterName = static::getParameterName($parameter->getName(), $parameters)) {
                continue;
            }

            $parameterValue = $parameters[$parameterName];

            $backedEnumClass = (string) $parameter->getType();

            $backedEnum = $backedEnumClass::tryFrom((string) $parameterValue);

            if (is_null($backedEnum)) {
                throw new BackedEnumCaseNotFoundException($backedEnumClass, $parameterValue);
            }

            $route->setParameter($parameterName, $backedEnum);
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
