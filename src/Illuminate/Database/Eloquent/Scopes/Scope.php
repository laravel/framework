<?php

namespace Illuminate\Database\Eloquent\Scopes;

use Illuminate\Database\Eloquent\Builder;

class Scope
{

    /**
     * The scope apply callback.
     *
     * @var callable
     */
    protected $apply;

    /**
     * The builder instance.
     *
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $builder;

    /**
     * Create a new local scope instance.
     *
     * @param  callable|null  $apply
     * @return void
     */
    public function __construct(callable $apply = null)
    {
        $this->apply = $apply;
    }

    /**
     * Invoke with builder for additional query constraints.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return static
     */
    public function __invoke(Builder $builder): static
    {
        $this->builder = $builder;
        return $this;
    }

    /**
     * Handle when additional methods are called against the scope.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        if (!$this->builder) {
            $this->__invoke($this->apply->__invoke());
        }
        return $this->builder->$method(...$parameters);
    }

    /**
     * Create a new local scope instance.
     *
     * @param  callable|null  $apply
     * @return static
     */
    public static function make(callable $apply = null): static
    {
        return new static($apply);
    }

}
