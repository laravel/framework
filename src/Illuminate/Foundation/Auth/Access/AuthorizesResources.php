<?php

namespace Illuminate\Foundation\Auth\Access;

use Illuminate\Routing\ControllerMiddlewareOptions;

trait AuthorizesResources
{
    /**
     * Authorize a resource action.
     *
     * @param  string  $name
     * @param  string  $model
     * @param  array  $options
     * @param  \Illuminate\Http\Request|null  $request
     * @return \Illuminate\Routing\ControllerMiddlewareOptions
     */
    public function authorizeResource($name, $model, array $options = [], $request = null)
    {
        $action = with($request ?: request())->route()->getActionName();

        $method = array_last(explode('@', $action));

        $map = [
            'index' => 'view', 'create' => 'create', 'store' => 'create', 'show' => 'view',
            'edit' => 'update', 'update' => 'update', 'delete' => 'delete',
        ];

        if (property_exists($this, 'abilityMap')) {
            $map = array_merge($map, $this->abilityMap);
        }

        if (! in_array($method, array_keys($map))) {
            return new ControllerMiddlewareOptions($options);
        }

        $model = in_array($method, ['index', 'create', 'store']) ? $model : $name;

        return $this->middleware("can:{$map[$method]},{$model}", $options);
    }
}
