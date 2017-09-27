<?php

namespace Illuminate\Routing;

use BadMethodCallException;

abstract class Controller
{
    /**
     * The middleware registered on the controller.
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * Register middleware on the controller.
     *
     * @param  array|string|\Closure  $middleware
     * @param  array   $options
     * @return \Illuminate\Routing\ControllerMiddlewareOptions
     */
    public function middleware($middleware, array $options = [])
    {
        foreach ((array) $middleware as $m) {
            $this->middleware[] = [
                'middleware' => $m,
                'options' => &$options,
            ];
        }

        return new ControllerMiddlewareOptions($options);
    }

    /**
     * Get the middleware assigned to the controller.
     *
     * @return array
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }

    /**
     * Execute an action on the controller.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function callAction($method, $parameters)
    {
        return call_user_func_array([$this, $method], $parameters);
    }

    /**
     * Handle calls to missing methods on the controller.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        throw new BadMethodCallException("Method [{$method}] does not exist.");
    }
    
    /**
     * Returns the view in a folder with same structure as namespace, function name as the name
     *
     * @param  array   $vars
     * @return \Illuminate\Contracts\View\View
     */
    public function view($vars=[])
    {
        $class    = strtolower(debug_backtrace()[1]['class']);
        $function = strtolower(debug_backtrace()[1]['function']);

        //transform namespace to folder structure
        $dir = preg_replace('/controller$/', '',
            str_replace('app.http.controllers.', '',
                str_replace('\\', '.', $class)
            )
        );

        //function becomes filename
        $defaultView = $dir . '.' . $function;

        return view($defaultView,$vars);
    }
}
