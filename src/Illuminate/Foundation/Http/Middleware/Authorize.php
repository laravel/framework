<?php

namespace Illuminate\Foundation\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Access\Gate;

class Authorize
{
    /**
     * The gate instance.
     *
     * @var \Illuminate\Contracts\Auth\Access\Gate
     */
    protected $gate;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Access\Gate  $gate
     * @return void
     */
    public function __construct(Gate $gate)
    {
        $this->gate = $gate;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $ability
     * @return mixed
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function handle($request, Closure $next, $ability)
    {
        $args = func_get_args();
        $model_args = array_slice($args, 3);
        $this->gate->authorize($ability, $this->getGateArguments($request, $model_args));

        return $next($request);
    }

    /**
     * Get the arguments parameter for the gate.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array|null  $model_args
     * @return array|string|\Illuminate\Database\Eloquent\Model
     */
    protected function getGateArguments($request, $model_args)
    {
        // If there's no model, we'll pass an empty array to the gate. If it
        // looks like a FQCN of a model, we'll send it to the gate as is.
        // Otherwise, we'll resolve the Eloquent model from the route.
        if (is_null($model_args)) {
            return [];
        }

        $gate_args = [];
        foreach ($model_args as $model) {
            if (strpos($model, '\\') !== false) {
                $gate_args[] = $model;
            } else {
                $gate_args[] = $request->route($model);
            }
        }

        return $gate_args;
    }
}
