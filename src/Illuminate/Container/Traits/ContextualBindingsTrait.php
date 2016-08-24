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
        // $this->when = $concrete;

        // return $this;
    }

    /**
     * Define the abstract target that depends on the context.
     *
     * @param  string  $abstract
     * @return $this
     */
    public function needs($abstract)
    {
        $this->needs = $abstract;

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

    private function getContextualBinding()
    {
    }
}
