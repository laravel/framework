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
        $action = with($request ?: request())->route()->getActionName();

        $map = [
            'index' => 'view', 'create' => 'create', 'store' => 'create', 'show' => 'view',
            'edit' => 'update', 'update' => 'update', 'delete' => 'delete',
        ];

        if (! in_array($method = array_last(explode('@', $action)), array_keys($map))) {
            return new ControllerMiddlewareOptions($options);
        }

        $name = $name ?: strtolower(class_basename($model));

        $model = in_array($method, ['index', 'create', 'store']) ? $model : $name;

        return $this->middleware("can:{$map[$method]},{$model}", $options);
    }
}
