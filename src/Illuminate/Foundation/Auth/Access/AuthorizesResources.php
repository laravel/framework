<?php

namespace Illuminate\Foundation\Auth\Access;

use Illuminate\Routing\ControllerMiddlewareOptions;

trait AuthorizesResources
{
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
            'index'   => 'view',
            'create'  => 'create',
            'store'   => 'create',
            'show'    => 'view',
            'edit'    => 'update',
            'update'  => 'update',
            'destroy' => 'delete',
        ];
    }
}
