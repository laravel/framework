<?php

namespace Illuminate\Routing;

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
     * Create a new controller instance.
     *
     * @param  \Illuminate\Contracts\View\Factory  $view
     * @return void
     */
    public function __construct(ViewFactory $view)
    {
        $this->view = $view;
    }

    /**
     * Invoke the controller method.
     *
     * @param  array  $args
     * @return \Illuminate\Contracts\View\View
     */
    public function __invoke(...$args)
    {
        [$view, $data] = array_slice($args, -2);

        return $this->view->make($view, $data);
    }
}
