<?php

namespace Illuminate\Pipeline;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Facade;
use Throwable;

trait Pipable
{
    /**
     * @param  array|string|callable  $pipe
     * @param  string  $via
     * @param  \Illuminate\Contracts\Container\Container|null  $container
     *
     * @return $this
     */
    public function pipe($pipe, $via = 'handle', ?Container $container = null)
    {
        return new class($this, $pipe, $via, $container)
        {
            /**
             * The container implementation.
             *
             * @var \Illuminate\Contracts\Container\Container
             */
            private $container;

            /**
             * The pipable object.
             *
             * @var object
             */
            private $pipable;

            /**
             * The array of pipes.
             *
             * @var array|string
             */
            private $pipes;

            /**
             * The method to call on the pipes.
             *
             * @var string
             */
            private $via;

            public function __construct($pipable, $pipes, $via, ?Container $container)
            {
                $this->pipable = $pipable;
                $this->pipes = $pipes;
                $this->via = $via;
                $this->container = $container ?: Facade::getFacadeApplication();
            }

            public function __call($method, $params)
            {
                $pipeline = new Pipeline($this->container);

                $core = (function ($params) use ($method) {
                    try {
                        return $this->$method(...$params);
                    } catch (Throwable $e) {
                        return $e;
                    }
                })->bindTo($this->pipable, $this->pipable);

                $result = $pipeline->via($this->via)->send($params)->through($this->pipes)->then($core);

                if ($result instanceof Throwable) {
                    throw $result;
                }

                return $result;
            }
        };
    }
}
