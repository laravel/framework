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
        $this->when = self::normalize($concrete);

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
        $abstract = ($abstract[0] === '$') ? substr($abstract, 1) : $abstract;

        $this->needs = self::normalize($abstract);

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

    /**
     * Get a contextual binding
     *
     * @param  string  $when
     * @param  string  $needs
     * @return void
     */
    private function getContextualBinding($when, $needs)
    {
        $hash = crc32($when . $needs);

        return (isset($this->contextualBindings[$hash])) ? $this->contextualBindings[$hash] : null;
    }

    /**
     * Get a contextual binding from a reflection parameter
     *
     * @param  \ReflectionParameter  $parameter
     * @return mixed
     */
    private function resolveContextualBinding(\ReflectionParameter $parameter)
    {
        $when = $this->buildStack[count($this->buildStack) - 1];
        $implementation = $this->getContextualBinding($when, $parameter->getName());

        if (!$implementation && ($class = $parameter->getClass())) {
            $implementation = $this->getContextualBinding($when, $class->name);
        }

        return $implementation;
    }
}
