<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Closure;
use Illuminate\Foundation\Mix;
use Mockery;

trait InteractsWithContainer
{
    /**
     * The original Laravel Mix handler.
     *
     * @var \Illuminate\Foundation\Mix|null
     */
    protected $originalMix;

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
     * @param  \Closure|null  $mock
     * @return \Mockery\MockInterface
     */
    protected function mock($abstract, Closure $mock = null)
    {
        return $this->singletonInstance($abstract, Mockery::mock(...array_filter(func_get_args())));
    }

    /**
     * Mock a partial instance of an object in the container.
     *
     * @param  string  $abstract
     * @param  \Closure|null  $mock
     * @return \Mockery\MockInterface
     */
    protected function partialMock($abstract, Closure $mock = null)
    {
        return $this->singletonInstance($abstract, Mockery::mock(...array_filter(func_get_args()))->makePartial());
    }

    /**
     * Spy an instance of an object in the container.
     *
     * @param  string  $abstract
     * @param  \Closure|null  $mock
     * @return \Mockery\MockInterface
     */
    protected function spy($abstract, Closure $mock = null)
    {
        return $this->singletonInstance($abstract, Mockery::spy(...array_filter(func_get_args())));
    }

    /**
     * Register an instance of an object as a singleton in the container.
     *
     * @param  string  $abstract
     * @param  \Mockery\MockInterface  $instance
     * @return \Mockery\MockInterface
     */
    protected function singletonInstance($abstract, $instance)
    {
        $this->app->singleton($abstract, function () use ($instance) {
            return $instance;
        });

        return $instance;
    }

    /**
     * Register an empty handler for Laravel Mix in the container.
     *
     * @return $this
     */
    protected function withoutMix()
    {
        if ($this->originalMix == null) {
            $this->originalMix = app(Mix::class);
        }

        $this->swap(Mix::class, function () {
            return '';
        });

        return $this;
    }

    /**
     * Register an empty handler for Laravel Mix in the container.
     *
     * @return $this
     */
    protected function withMix()
    {
        if ($this->originalMix) {
            $this->app->instance(Mix::class, $this->originalMix);
        }

        return $this;
    }
}
