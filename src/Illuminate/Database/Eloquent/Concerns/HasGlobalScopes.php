<?php

namespace Illuminate\Database\Eloquent\Concerns;

trait HasGlobalScopes
{
    /**
     * Sets the Eloquent Scopes hub for models.
     *
     * @param  \Illuminate\Database\Eloquent\Scopes  $eloquentScopes
     * @return void
     */
    public static function setEloquentScopes($eloquentScopes)
    {
        static::$globalScopes = $eloquentScopes;
    }

    /**
     * Unset the Eloquent Scopes hub for models.
     *
     * @return void
     */
    public static function unsetEloquentScopes()
    {
        static::$globalScopes = null;
    }

    /**
     * Register a new global scope on the model.
     *
     * @param  \Illuminate\Database\Eloquent\Scope|\Closure|string  $scope
     * @param  \Illuminate\Database\Eloquent\Scope|\Closure|null  $implementation
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public static function addGlobalScope($scope, $implementation = null)
    {
        return static::$globalScopes->addGlobalScope(static::class, $scope, $implementation);
    }

    /**
     * Determine if a model has a global scope.
     *
     * @param  \Illuminate\Database\Eloquent\Scope|string  $scope
     * @return bool
     */
    public static function hasGlobalScope($scope)
    {
        return static::$globalScopes->hasGlobalScope(static::class, $scope);
    }

    /**
     * Get a global scope registered with the model.
     *
     * @param  \Illuminate\Database\Eloquent\Scope|string  $scope
     * @return \Illuminate\Database\Eloquent\Scope|\Closure|null
     */
    public static function getGlobalScope($scope)
    {
        return static::$globalScopes->getGlobalScope(static::class, $scope);
    }

    /**
     * Get the global scopes for this class instance.
     *
     * @return array<string, array<\Illuminate\Database\Eloquent\Scope|string|callable>>
     */
    public function getGlobalScopes()
    {
        return static::$globalScopes->getGlobalScopesForModel(static::class);
    }
}
