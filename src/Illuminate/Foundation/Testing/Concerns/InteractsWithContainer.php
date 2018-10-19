<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Closure;
use Mockery as m;

trait InteractsWithContainer
{
    /**
     * Register an instance of an object in the container.
     *
     * @param  string  $abstract
     * @param  object  $instance
     * @return object
     */
    protected function swap($abstract, $instance)
    {
        return $this->instance($abstract, $instance);
    }

    /**
     * Register an instance of an object in the container.
     *
     * @param  string  $abstract
     * @param  object  $instance
     * @return object
     */
    protected function instance($abstract, $instance)
    {
        $this->app->instance($abstract, $instance);

        return $instance;
    }

    /**
     * Mock an instance of an object in the container.
     *
     * @param  string  $abstract
     * @param  \Closure  $instance
     * @return object
     */
    protected function mock($abstract, Closure $mock)
    {
        return $this->instance($abstract, m::mock($abstract, $mock));
    }

    /**
     * Spy an instance of an object in the container.
     *
     * @param  string  $abstract
     * @param  \Closure  $instance
     * @return object
     */
    protected function spy($abstract, Closure $mock)
    {
        return $this->instance($abstract, m::spy($abstract, $mock));
    }
}
