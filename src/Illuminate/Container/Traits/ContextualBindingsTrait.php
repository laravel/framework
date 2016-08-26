<?php

namespace Illuminate\Container\Traits;

use ReflectionClass;
use Illuminate\Container\ContainerResolver;
use Illuminate\Contracts\Container\BindingResolutionException as Exception;

trait ContextualBindingsTrait
{
    private $concrete;
    private $abstract;
    private $parameter;
    private $contextualParameters = [];

    /**
     * Define a contextual binding.
     *
     * @param  string  $abstract
     * @return ContextualBindingsTrait
     */
    public function when($abstract)
    {
        $this->abstract = self::normalize($abstract);

        if (isset($this->bindings[$this->abstract])) {
            $this->concrete = $this->bindings[$this->abstract][ContainerResolver::VALUE];
        } else if (strpos($abstract, '@')) {
            $this->concrete = explode('@', $abstract, 2);
        } else {
            $this->concrete = $this->abstract;
        }

        return $this;
    }

    /**
     * Define the abstract target that depends on the context.
     *
     * @param  string  $parameter
     * @return ContextualBindingsTrait
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
        if (!($reflector = ContainerResolver::getReflector($this->concrete))) {
            throw new Exception("[$this->concrete] is not resolvable.");
        }
        if ($reflector instanceof ReflectionClass && !($reflector = $reflector->getConstructor())) {
            throw new Exception("[$this->concrete] must have a constructor.");
        }

        $reflectionParameters = $reflector->getParameters();

        foreach ($reflectionParameters as $key => $parameter) {
            $class = $parameter->getClass();

            if ($this->parameter === $parameter->name || $class && $this->parameter === $class->name) {
                $this->contextualParameters[$this->abstract][$key] = $implementation;

                return ;
            }
        }

        throw new Exception("Parameter [$this->parameter] not found in [$this->concrete].");
    }
}
