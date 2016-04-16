<?php

namespace Illuminate\Foundation\Auth\Access;

use Illuminate\Contracts\Auth\Access\Gate;

trait AuthorizesResources
{
    /**
     * Authorize a resource action based on the incoming request.
     *
     * @param  string  $model
     * @param  string|null  $name
     * @param  \Illuminate\Http\Request|null  $request
     * @return \Illuminate\Routing\ControllerMiddlewareOptions
     */
    public function authorizeResource($model, $name = null, $request = null)
    {
        $action = with($request ?: request())->route()->getActionName();

        $map = [
            'index' => 'view', 'create' => 'create', 'store' => 'create', 'show' => 'view',
            'edit' => 'update', 'update' => 'update', 'delete' => 'delete',
        ];

        $method = array_last(explode('@', $action));

        $name = $name ?: strtolower(class_basename($model));

        $model = in_array($method, ['index', 'create', 'store']) ? $model : $name;

        return app(Gate::class)->authorize($map[$method], [$model]);
    }
}
