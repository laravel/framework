<?php

namespace Illuminate\Container\Traits;

use Illuminate\Container\ContainerResolver;

trait ContextualBindingsTrait
{
    private $concrete;
    private $avstract;

    /**
     * Define a contextual binding.
     *
     * @param  string  $concrete
     * @return \Illuminate\Contracts\Container\ContextualBindingBuilder
     */
    public function when($concrete)
    {
        $this->concrete = self::normalize($concrete);

        if (isset($this->bindings[$this->concrete])) {
            $this->concrete = $this->bindings[$this->concrete][ContainerResolver::VALUE];
        }

        return $this;
    }

    /**
     * Define the abstract target that depends on the context.
     *
     * @param  string  $abstract
     * @return $this
     */
    public function needs($abstract)
    {
        $this->abstract = self::normalize($abstract);

        if ($this->abstract[0] === '$') {
            $this->abstract = substr($this->abstract, 1);
        }

        return $this;
    }

    /**
     * Define the implementation for the contextual binding.
     *
     * @param  \Closure|string  $implementation
     * @return void
     */
    public function give($implementation)
    {
    }
}
