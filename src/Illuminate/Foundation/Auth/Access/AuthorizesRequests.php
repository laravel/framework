<?php

namespace Illuminate\Foundation\Auth\Access;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Routing\ControllerMiddlewareOptions;

trait AuthorizesRequests
{
    /**
     * Authorize a given action against a set of arguments.
     *
     * @param  mixed  $ability
     * @param  mixed|array  $arguments
     * @return \Illuminate\Auth\Access\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorize($ability, $arguments = [])
    {
        list($ability, $arguments) = $this->parseAbilityAndArguments($ability, $arguments);

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
        list($ability, $arguments) = $this->parseAbilityAndArguments($ability, $arguments);

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
        if (is_string($ability)) {
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

        return isset($map[$ability]) ? $map[$ability] : $ability;
    }

    /**
     * Authorize a resource action based on the incoming request.
     *
     * @param  string  $model
     * @param  string|null  $name
     * @param  array  $options
     * @param  \Illuminate\Http\Request|null  $request
     * @return \Illuminate\Routing\ControllerMiddlewareOptions
     */
    public function authorizeResource($model, $name = null, array $options = [], $request = null)
    {
        $method = array_last(explode('@', with($request ?: request())->route()->getActionName()));

        $map = $this->resourceAbilityMap();

        if (! in_array($method, array_keys($map))) {
            return new ControllerMiddlewareOptions($options);
        }

        if (! in_array($method, ['index', 'create', 'store'])) {
            $model = $name ?: strtolower(class_basename($model));
        }

        return $this->middleware("can:{$map[$method]},{$model}", $options);
    }

    /**
     * Get the map of resource methods to ability names.
     *
     * @return array
     */
    protected function resourceAbilityMap()
    {
        return [
            'index'  => 'view',
            'create' => 'create',
            'store'  => 'create',
            'show'   => 'view',
            'edit'   => 'update',
            'update' => 'update',
            'delete' => 'delete',
        ];
    }
}
