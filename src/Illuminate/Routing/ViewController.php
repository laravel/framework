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
    protected $factory;

    /**
     * Create a new controller instance.
     *
     * @param  \Illuminate\Contracts\View\Factory  $factory
     * @return void
     */
    public function __construct(ViewFactory $factory)
    {
        $this->factory = $view;
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

        return $this->factory->make($view, $data);
    }
}
