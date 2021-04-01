<?php

namespace Illuminate\Foundation\Auth\Access;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ReflectionClass;

trait AuthorizesRequests
{
    /**
     * Authorize a given action for the current user.
     *
     * @param  mixed  $ability
     * @param  mixed|array  $arguments
     * @return \Illuminate\Auth\Access\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorize($ability, $arguments = [])
    {
        [$ability, $arguments] = $this->parseAbilityAndArguments($ability, $arguments);

        return app(Gate::class)->authorize($ability, $arguments);
    }

    /**
     * Authorize a given action for a user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|mixed  $user
     * @param  mixed  $ability
     * @param  mixed|array  $arguments
     * @return \Illuminate\Auth\Access\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizeForUser($user, $ability, $arguments = [])
    {
        [$ability, $arguments] = $this->parseAbilityAndArguments($ability, $arguments);

        return app(Gate::class)->forUser($user)->authorize($ability, $arguments);
    }

    /**
     * Guesses the ability's name if it wasn't provided.
     *
     * @param  mixed  $ability
     * @param  mixed|array  $arguments
     * @return array
     */
    protected function parseAbilityAndArguments($ability, $arguments)
    {
        if (is_string($ability) && strpos($ability, '\\') === false) {
            return [$ability, $arguments];
        }

        $method = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[2]['function'];

        return [$this->normalizeGuessedAbilityName($method), $ability];
    }

    /**
     * Normalize the ability name that has been guessed from the method name.
     *
     * @param  string  $ability
     * @return string
     */
    protected function normalizeGuessedAbilityName($ability)
    {
        $map = $this->resourceAbilityMap();

        return $map[$ability] ?? $ability;
    }

    /**
     * Authorize a resource action based on the incoming request.
     *
     * @param  string  $model
     * @param  string|null  $parameter
     * @param  array  $options
     * @param  \Illuminate\Http\Request|null  $request
     * @return void
     */
    public function authorizeResource($model, $parameter = null, array $options = [], $request = null)
    {
        $parameter = $parameter ?: Str::snake(class_basename($model));

        $middleware = [];

        foreach ($this->resourceAbilityMap() as $method => $ability) {
            $modelName = in_array($method, $this->resourceMethodsWithoutModels()) ? $model : $parameter;

            $middleware["can:{$ability},{$modelName}"][] = $method;
        }

        foreach ($middleware as $middlewareName => $methods) {
            $this->middleware($middlewareName, $options)->only($methods);
        }
    }

    /**
     * Authorize all methods declared in the controller (except resource methods)
     *
     * @param $model
     * @param null $parameter
     * @param array $options
     * @throws \ReflectionException
     */
    public function authorizeMethods($model, $parameter = null, array $options = [])
    {
        $reflection = new ReflectionClass($this);

        $class = get_class($this);

        $traitMethods = collect($reflection->getTraits())
            ->reduce(function ($methods, $trait) {
                return $methods->merge($trait->getMethods());
            }, collect())
            ->map(function ($method) {
                return $method->name;
            });

        $methods = collect($reflection->getMethods())
            ->filter(function ($method) use ($class) {
                return $class == $method->getDeclaringClass()->name && !str_starts_with($method->getName(), '__');
            })
            ->map(function ($method) use ($class) {
                return $method->getName();
            })
            ->diff($traitMethods);

        $this->authorizeOnly($model, $methods, $parameter, $options);
    }

    /**
     * Authorize specified methods in the controller (resource methods are ignored)
     *
     * @param $model
     * @param iterable $methods
     * @param null $parameter
     * @param array $options
     * @throws \ReflectionException
     */
    public function authorizeOnly($model, iterable $methods, $parameter = null, array $options = [])
    {
        $reflection = new ReflectionClass($this);

        $middleware = [];

        foreach ($methods as $method) {
            if (isset($this->resourceAbilityMap()[$method])) {
                continue;
            }

            $binding = Arr::first($reflection->getMethod($method)->getParameters(),
                function ($param) use ($model, $parameter) {
                    return $param->name == $parameter && $param->getType() && $param->getType()->getName() == $model;
                });

            $modelName = $binding ? $parameter : $model;

            $middleware["can:{$method},{$modelName}"][] = $method;
        }

        foreach ($middleware as $middlewareName => $methods) {
            $this->middleware($middlewareName, $options)->only($methods);
        }
    }

    /**
     * Get the map of resource methods to ability names.
     *
     * @return array
     */
    protected function resourceAbilityMap()
    {
        return [
            'index' => 'viewAny',
            'show' => 'view',
            'create' => 'create',
            'store' => 'create',
            'edit' => 'update',
            'update' => 'update',
            'destroy' => 'delete',
        ];
    }

    /**
     * Get the list of resource methods which do not have model parameters.
     *
     * @return array
     */
    protected function resourceMethodsWithoutModels()
    {
        return ['index', 'create', 'store'];
    }
}
