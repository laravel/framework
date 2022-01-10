<?php

namespace Illuminate\Routing;

use BadMethodCallException;
use Illuminate\Attributes\Routing\Middleware;
use ReflectionClass;

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
     * @param  \Closure|array|string  $middleware
     * @param  array  $options
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
        return array_merge($this->middleware, $this->getMiddlewaresByAttributes());
    }

    /**
     * Get the controller middlewares by attributes.
     *
     * @see \Illuminate\Attributes\Routing\Middleware
     *
     * @return array
     */
    public function getMiddlewaresByAttributes()
    {
        $middlewares = [];

        // PHP 8+
        if (80000 > PHP_VERSION_ID) {
            return $middlewares;
        }

        /** @var \ReflectionAttribute[] $attributes */
        $push = function (array $attributes, ?string $method = null) use (&$middlewares) {
            foreach ($attributes as $attribute) {
                /** @var Middleware $middlewares */
                $middleware = $attribute->newInstance();

                $name = $middleware->name;
                $arguments = count($middleware->arguments) ? ':'.implode(',', $middleware->arguments) : '';
                $options = $middleware->options;

                $middlewares[] = [
                    'middleware' => "$name$arguments",
                    'options' => &$options,
                ];

                if ($method) {
                    (new ControllerMiddlewareOptions($options))->only($method);
                }
            }
        };

        $class = new ReflectionClass($this);

        // Class
        $push($class->getAttributes(Middleware::class));

        // Methods
        foreach ($class->getMethods() as $method) {
            $push($method->getAttributes(Middleware::class), $method->name);
        }

        return $middlewares;
    }

    /**
     * Execute an action on the controller.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function callAction($method, $parameters)
    {
        return $this->{$method}(...array_values($parameters));
    }

    /**
     * Handle calls to missing methods on the controller.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        throw new BadMethodCallException(sprintf(
            'Method %s::%s does not exist.', static::class, $method
        ));
    }
}
