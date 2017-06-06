<?php

namespace Illuminate\Pipeline;

use Closure;
use Exception;
use Illuminate\Contracts\Container\Container;

class OrPipe
{
    /**
     * The container implementation.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * The method to call on each pipe.
     *
     * @var string
     */
    protected $method = 'handle';

    /**
     * OrPipe constructor.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @param  string  $method
     */
    public function __construct(Container $container, $method)
    {
        $this->container = $container;
        $this->method = $method;
    }

    /**
     * Handle an or pipe.
     *
     * @param  mixed  $passable
     * @param  \Closure  $stack
     * @param  array  $pipes
     *
     * @return mixed
     * @throws \Exception
     */
    public function handle($passable, $stack, array $pipes)
    {
        foreach ($pipes as $index => $pipe) {
            list($name, $parameters) = $pipe;
            $parameters = array_merge([$passable, $stack], $parameters);

            $pipe = $this->container->make($name);

            try {
                $result = method_exists($pipe, $this->method)
                    ? $pipe->{$this->method}(...$parameters)
                    : $pipe(...$parameters);

                if (! ($result instanceof Closure || $index === count($pipes) - 1)) {
                    continue;
                }

                return $result;
            } catch (Exception $e) {
                // If this is the final pipe that we are running,
                // allow the exception to bubble up, and allow
                // the application to handle it as any pipe.
                if ($index === count($pipes) - 1) {
                    throw $e;
                }

                continue;
            }
        }
    }
}
