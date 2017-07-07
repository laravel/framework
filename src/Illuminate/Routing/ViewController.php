<?php

namespace Illuminate\Routing;

use Illuminate\Http\Request;
use Illuminate\Contracts\View\Factory as ViewFactory;

class ViewController extends Controller
{
    /**
     * The view factory implementation.
     *
     * @var \Illuminate\Contracts\View\Factory
     */
    protected $view;

    /**
     * The default data for the view.
     *
     * @var array;
     */
    protected $data;

    /**
     * Create a new controller instance.
     *
     * @param  \Illuminate\Contracts\View\Factory  $view
     * @param  \Illuminate\Routing\Route           $route
     * @param  \Illuminate\Http\Request            $request
     * @return void
     */
    public function __construct(ViewFactory $view, Route $route, Request $request)
    {
        $this->view = $view;
        $this->data = $route->parameters + compact('request');
    }

    /**
     * Invoke the controller method.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return \Illuminate\Contracts\View\View
     */
    public function __invoke(Route $route)
    {
        $view = $route->defaults['view'];
        $data = $route->defaults['data'];

        $data = array_merge($data, $this->data);

        return $this->view->make($view, $data);
    }
}
