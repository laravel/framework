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
     * @param  string  $view
     * @param  array  $data
     * @return \Illuminate\Contracts\View\View
     */
    public function __invoke($view, $data)
    {
        return $this->view->make($view, $data);
    }
}
