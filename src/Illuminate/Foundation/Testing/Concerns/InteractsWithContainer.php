<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Closure;
use Illuminate\Foundation\Mix;
use Illuminate\Foundation\Vite;
use Mockery;

trait InteractsWithContainer
{
    /**
     * The original Vite handler.
     *
     * @var \Illuminate\Foundation\Vite|null
     */
    protected $originalVite;

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
        return $this->instance($abstract, Mockery::mock(...array_filter(func_get_args())));
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
        return $this->instance($abstract, Mockery::mock(...array_filter(func_get_args()))->makePartial());
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
        return $this->instance($abstract, Mockery::spy(...array_filter(func_get_args())));
    }

    /**
     * Instruct the container to forget a previously mocked / spied instance of an object.
     *
     * @param  string  $abstract
     * @return $this
     */
    protected function forgetMock($abstract)
    {
        $this->app->forgetInstance($abstract);

        return $this;
    }

    /**
     * Register an empty handler for Vite in the container.
     *
     * @return $this
     */
    protected function withoutVite()
    {
        if ($this->originalVite == null) {
            $this->originalVite = app(Vite::class);
        }

        $this->swap(Vite::class, function () {
            return '';
        });

        return $this;
    }

    /**
     * Restore Vite in the container.
     *
     * @return $this
     */
    protected function withVite()
    {
        if ($this->originalVite) {
            $this->app->instance(Vite::class, $this->originalVite);
        }

        return $this;
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
     * Restore Laravel Mix in the container.
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
