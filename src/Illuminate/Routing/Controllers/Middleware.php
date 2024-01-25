<?php

namespace Illuminate\Routing\Controllers;

use Closure;
use Illuminate\Support\Arr;

class Middleware
{
    /**
     * The middleware that should be assigned.
     *
     * @var \Closure|string|array
     */
    public $middleware;

    /**
     * The controller methods the middleware should only apply to.
     *
     * @var array|null
     */
    public $only;

    /**
     * The controller methods the middleware should not apply to.
     *
     * @var array|null
     */
    public $except;

    /**
     * Create a new controller middleware definition.
     *
     * @param  \Closure|string|array  $middleware
     * @return void
     */
    public function __construct(Closure|string|array $middleware)
    {
        $this->middleware = $middleware;
    }

    /**
     * Specify the only controller methods the middleware should apply to.
     *
     * @param  array|string  $only
     * @return $this
     */
    public function only(array|string $only)
    {
        $this->only = Arr::wrap($only);

        return $this;
    }

    /**
     * Specify the controller methods the middleware should not apply to.
     *
     * @param  array|string  $except
     * @return $this
     */
    public function except(array|string $except)
    {
        $this->except = Arr::wrap($except);

        return $this;
    }
}
