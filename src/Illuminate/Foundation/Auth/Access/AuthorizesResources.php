<?php

namespace Illuminate\Foundation\Auth\Access;

use Illuminate\Routing\ControllerMiddlewareOptions;

trait AuthorizesResources
{
    /**
     * Map of resource methods to ability names.
     *
     * @var array
     */
    protected $resourceAbilityMap = [
        'index'  => 'view',
        'create' => 'create',
        'store'  => 'create',
        'show'   => 'view',
        'edit'   => 'update',
        'update' => 'update',
        'delete' => 'delete',
    ];

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

        if (! in_array($method, array_keys($this->resourceAbilityMap))) {
            return new ControllerMiddlewareOptions($options);
        }

        if (! in_array($method, ['index', 'create', 'store'])) {
            $model = $name ?: strtolower(class_basename($model));
        }

        return $this->middleware("can:{$this->resourceAbilityMap[$method]},{$model}", $options);
    }
}
