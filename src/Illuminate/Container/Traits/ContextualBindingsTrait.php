<?php

namespace Illuminate\Container\Traits;

use ReflectionClass;
use Illuminate\Container\ContainerResolver;

trait ContextualBindingsTrait
{
    private $concrete;
    private $abstract;
    private $parameter;
    private $contextualParameters = [];

    /**
     * Define a contextual binding.
     *
     * @param  string  $concrete
     * @return \Illuminate\Contracts\Container\ContextualBindingBuilder
     */
    public function when($abstract)
    {
        $this->abstract = self::normalize($abstract);

        if (isset($this->bindings[$this->abstract])) {
            $this->concrete = $this->bindings[$this->abstract][ContainerResolver::VALUE];
        } else {
            $this->concrete = $this->abstract;
        }

        return $this;
    }

    /**
     * Define the abstract target that depends on the context.
     *
     * @param  string  $parameter
     * @return $this
     */
    public function needs($parameter)
    {
        $this->parameter = self::normalize($parameter);

        if ($this->parameter[0] === '$') {
            $this->parameter = substr($this->parameter, 1);
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
        $reflectionClass = new ReflectionClass($this->concrete);
        $reflectionParameters = $reflectionClass->getConstructor()->getParameters();

        foreach ($reflectionParameters as $key => $parameter) {
            $class = $parameter->getClass();

            if ($this->parameter === $parameter->name || $class && $this->parameter === $class->name) {
                $this->contextualParameters[$this->abstract][$key] = $implementation;

                return ;
            }
        }
    }
}
