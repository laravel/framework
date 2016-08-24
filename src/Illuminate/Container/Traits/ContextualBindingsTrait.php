<?php

namespace Illuminate\Container\Traits;

trait ContextualBindingsTrait
{
    private $when;
    private $needs;
    private $contextualBindings = [];

    /**
     * Define a contextual binding.
     *
     * @param  string  $concrete
     * @return \Illuminate\Contracts\Container\ContextualBindingBuilder
     */
    public function when($concrete)
    {
        $this->when = $this->normalize($concrete);

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
        $this->needs = $this->normalize($abstract);

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
        $hash = crc32($this->when . $this->needs);

        $this->contextualBindings[$hash] = $implementation;
    }

    private function getContextualBinding($when, $needs)
    {
        $hash = crc32($this->normalize($when) . $this->normalize($needs));

        if (isset($this->contextualBindings[$hash])) {
            return $this->contextualBindings[$hash];
        }

        return null;
    }

    private function resolveContextualBinding(\ReflectionParameter $parameter)
    {
        $when = $this->buildStack[count($this->buildStack) - 1];
        $implementation = null;

        $parameterName = $parameter->getName();
        $parameterClass = $parameter->getClass();

        if ($parameterClass) {
            $implementation = $this->getContextualBinding($when, $parameterClass->name);
        }
        if (!$implementation) {
            $implementation = $this->getContextualBinding($when, "$" . $parameterName);
        }

        return $implementation;
    }
}
