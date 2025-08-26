<?php

namespace Illuminate\Routing;

use Illuminate\Auth\Attributes\Authorize;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Contracts\ControllerDispatcher as ControllerDispatcherContract;
use Illuminate\Support\Collection;
use ReflectionMethod;

class ControllerDispatcher implements ControllerDispatcherContract
{
    use FiltersControllerMiddleware, ResolvesRouteDependencies;

    /**
     * The container instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * Create a new controller dispatcher instance.
     *
     * @param  \Illuminate\Container\Container  $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Dispatch a request to a given controller and method.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  mixed  $controller
     * @param  string  $method
     * @return mixed
     */
    public function dispatch(Route $route, $controller, $method)
    {
        $parameters = $this->resolveParameters($route, $controller, $method);

        $this->handleAuthorizeAttribute($controller, $method, $parameters);

        if (method_exists($controller, 'callAction')) {
            return $controller->callAction($method, $parameters);
        }

        return $controller->{$method}(...array_values($parameters));
    }

    /**
     * Handle authorization attribute if present on the method.
     *
     * @param  mixed  $controller
     * @param  string  $method
     * @param  array  $parameters
     * @return void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function handleAuthorizeAttribute($controller, $method, $parameters)
    {
        $reflection = new ReflectionMethod($controller, $method);
        $attributes = $reflection->getAttributes(Authorize::class);

        if (empty($attributes)) {
            return;
        }

        /** @var \Illuminate\Auth\Attributes\Authorize $authorizeAttribute */
        $authorizeAttribute = $attributes[0]->newInstance();

        $gate = $this->container->make(Gate::class);

        $gateArguments = $this->resolveGateArguments($authorizeAttribute, $reflection, $parameters);

        $gate->authorize($authorizeAttribute->ability, $gateArguments);
    }

    /**
     * Resolve the gate arguments from the authorize attribute and method parameters.
     *
     * @param  \Illuminate\Auth\Attributes\Authorize  $attribute
     * @param  \ReflectionMethod  $method
     * @param  array  $parameters
     * @return array
     */
    protected function resolveGateArguments($attribute, $method, $parameters)
    {
        if (empty($attribute->models)) {
            return $this->autoResolveModelsFromParameters($method, $parameters);
        }

        $arguments = [];

        foreach ($attribute->models as $model) {
            if (is_string($model) && class_exists($model)) {
                // If it's a class name, try to find a matching parameter
                $arguments[] = $this->findParameterByType($method, $parameters, $model);
            } else {
                // If it's a parameter name, find it in the parameters
                $arguments[] = $parameters[$model] ?? $model;
            }
        }

        return array_filter($arguments, fn ($arg) => $arg !== null);
    }

    /**
     * Auto-resolve model arguments from method parameters.
     *
     * @param  \ReflectionMethod  $method
     * @param  array  $parameters
     * @return array
     */
    protected function autoResolveModelsFromParameters($method, $parameters)
    {
        $arguments = [];

        foreach ($method->getParameters() as $param) {
            $type = $param->getType();

            if ($type && ! $type->isBuiltin() && class_exists($type->getName())) {
                $typeName = $type->getName();

                if (is_subclass_of($typeName, Model::class)) {
                    $paramName = $param->getName();
                    if (isset($parameters[$paramName])) {
                        $arguments[] = $parameters[$paramName];
                    }
                }
            }
        }

        return $arguments;
    }

    /**
     * Find a parameter by its type.
     *
     * @param  \ReflectionMethod  $method
     * @param  array  $parameters
     * @param  string  $typeName
     * @return mixed
     */
    protected function findParameterByType($method, $parameters, $typeName)
    {
        foreach ($method->getParameters() as $param) {
            $type = $param->getType();

            if ($type && $type->getName() === $typeName) {
                $paramName = $param->getName();

                return $parameters[$paramName] ?? null;
            }
        }

        return null;
    }

    /**
     * Resolve the parameters for the controller.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  mixed  $controller
     * @param  string  $method
     * @return array
     */
    protected function resolveParameters(Route $route, $controller, $method)
    {
        return $this->resolveClassMethodDependencies(
            $route->parametersWithoutNulls(), $controller, $method
        );
    }

    /**
     * Get the middleware for the controller instance.
     *
     * @param  \Illuminate\Routing\Controller  $controller
     * @param  string  $method
     * @return array
     */
    public function getMiddleware($controller, $method)
    {
        if (! method_exists($controller, 'getMiddleware')) {
            return [];
        }

        return (new Collection($controller->getMiddleware()))
            ->reject(fn ($data) => static::methodExcludedByOptions($method, $data['options']))
            ->pluck('middleware')
            ->all();
    }
}
