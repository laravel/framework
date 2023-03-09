<?php

namespace Illuminate\Routing;

use Illuminate\Contracts\Routing\UrlRoutable;
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

        $route = static::resolveBackedEnumsForRoute($route, $parameters);

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

            $routeBindingMethod = $route->allowsTrashedBindings() && in_array(SoftDeletes::class, class_uses_recursive($instance))
                        ? 'resolveSoftDeletableRouteBinding'
                        : 'resolveRouteBinding';

            if ($parent instanceof UrlRoutable &&
                ! $route->preventsScopedBindings() &&
                ($route->enforcesScopedBindings() || array_key_exists($parameterName, $route->bindingFields()))) {
                $childRouteBindingMethod = $route->allowsTrashedBindings() && in_array(SoftDeletes::class, class_uses_recursive($instance))
                            ? 'resolveSoftDeletableChildRouteBinding'
                            : 'resolveChildRouteBinding';

                if (! $model = static::getModel($parent, $childRouteBindingMethod, $parameterName, $parameterValue, $route)) {
                    throw (new ModelNotFoundException)->setModel(get_class($instance), [$parameterValue]);
                }
            } elseif (! $model = static::getModel($instance, $routeBindingMethod, $parameterName, $parameterValue, $route)) {
                throw (new ModelNotFoundException)->setModel(get_class($instance), [$parameterValue]);
            }

            $route->setParameter($parameterName, $model);
        }
    }

    /**
     * get the model form the route.
     *
     * @param  \Illuminate\Database\Eloquent\Model |  \Illuminate\Contracts\Routing\UrlRoutable $instance
     * @param  string  $binding_method
     * @param  string  $parameterName
     * @param  string  $parameterValue
     * @param  \Illuminate\Routing\Route  $route
     * @return \Illuminate\Database\Eloquent\Model | null
     */
    public static function getModel($instance, $binding_method, $parameterName, $parameterValue, $route)
    {
        $fields = (array) $route->bindingFieldFor($parameterName);

        $parameters = str_contains($binding_method, 'Child') ? [$parameterName, $parameterValue] : [$parameterValue];

        if (!$fields) {
            return $instance->{$binding_method}(...array_merge($parameters, [null]));
        }

        foreach ($fields as $field) {
            if ($model = $instance->{$binding_method}(...array_merge($parameters, [$field]))) {
                return $model;
            }
        }

        return null;
    }

    /**
     * Resolve the Backed Enums route bindings for the route.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  array  $parameters
     * @return \Illuminate\Routing\Route
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

        return $route;
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
