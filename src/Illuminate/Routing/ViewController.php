<?php

namespace Illuminate\Routing;

use Illuminate\Container\Container;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Support\Arr;

class ViewController extends Controller
{
    /**
     * The response factory implementation.
     *
     * @var \Illuminate\Contracts\Routing\ResponseFactory
     */
    protected $response;

    /**
     * The IoC container instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * Create a new controller instance.
     *
     * @param  \Illuminate\Contracts\Routing\ResponseFactory  $response
     * @param  \Illuminate\Container\Container  $container
     * @return void
     */
    public function __construct(ResponseFactory $response, Container $container)
    {
        $this->response = $response;
        $this->container = $container;
    }

    /**
     * Invoke the controller method.
     *
     * @param  array  $args
     * @return \Illuminate\Http\Response
     */
    public function __invoke(...$args)
    {
        [$view, $data, $status, $headers] = array_slice($args, -4);

        if (! Arr::isAssoc($data)) {
            $params = array_slice($args, 0, -4);
            $data = $this->container->make($data[0])->{$data[1]}(...$params);
        }

        return $this->response->view($view, $data, $status, $headers);
    }
}
