<?php

namespace Illuminate\Database\Eloquent;

use Closure;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class Scopes
{
    /**
     * Scopes registered for each model.
     *
     * @var array<string, array<\Illuminate\Database\Eloquent\Scope|string|callable>>
     */
    public $scopes = [];

    /**
     * Determine if a model has a global scope.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     * @param  \Illuminate\Database\Eloquent\Scope|string  $scope
     * @return bool
     */
    public function hasGlobalScope($model, $scope)
    {
        return ! is_null($this->getGlobalScope($model, $scope));
    }

    /**
     * Determine if a model has any global scope registered.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     * @return bool
     */
    public function hasScopes($model)
    {
        return ! empty($this->getGlobalScope($model, '*'));
    }

    /**
     * Get a global scope registered with the model.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     * @param  \Illuminate\Database\Eloquent\Scope|string  $scope
     * @return \Illuminate\Database\Eloquent\Scope|\Closure|null
     */
    public function getGlobalScope($model, $scope)
    {
        if (is_object($model)) {
            $model = get_class($model);
        }

        if (is_object($scope)) {
            $scope = get_class($scope);
        }

        return Arr::get($this->scopes, $model.'.'.$scope);
    }

    /**
     * Return all the Global Scopes registered for an Eloquent Model.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     * @return array<string, array<\Illuminate\Database\Eloquent\Scope|string|callable>>
     */
    public function getGlobalScopesForModel($model)
    {
        if (is_object($model)) {
            $model = get_class($model);
        }

        return Arr::get($this->scopes, $model, []);
    }

    /**
     * Register a new global scope on the model.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     * @param  \Illuminate\Database\Eloquent\Scope|\Closure|string  $scope
     * @param  \Illuminate\Database\Eloquent\Scope|\Closure|null  $implementation
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function addGlobalScope($model, $scope, $implementation = null)
    {
        if (is_object($model)) {
            $model = get_class($model);
        }

        if (is_string($scope) && ($implementation instanceof Closure || $implementation instanceof Scope)) {
            return $this->scopes[$model][$scope] = $implementation;
        } elseif ($scope instanceof Closure) {
            return $this->scopes[$model][spl_object_hash($scope)] = $scope;
        } elseif ($scope instanceof Scope) {
            return $this->scopes[$model][get_class($scope)] = $scope;
        }

        throw new InvalidArgumentException('Global scope must be an instance of Closure or Scope.');
    }

    /**
     * Returns the global scopes.
     *
     * @return array<string, array<\Illuminate\Database\Eloquent\Scope|string|callable>>
     */
    public function getGlobalScopes()
    {
        return $this->scopes;
    }

    /**
     * Flushes all the Global Scopes for the given model.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     * @return void
     */
    public function flushGlobalScopesForModel($model)
    {
        if (is_object($model)) {
            $model = get_class($model);
        }

        $this->scopes[$model] = [];
    }

    /**
     * Sets all the global scopes.
     *
     * @param  array<string, array<\Illuminate\Database\Eloquent\Scope|string|callable>>  $scopes
     * @return void
     */
    public function setGlobalScopes(array $scopes)
    {
        $this->scopes = $scopes;
    }

    /**
     * Merges the global scopes on top of the already set global scopes.
     *
     * @param  array  $scopes
     * @return void
     */
    public function mergeGlobalScopes(array $scopes)
    {
        $this->scopes = array_merge_recursive($this->scopes, $scopes);
    }
}
