<?php

namespace Illuminate\Contracts\Database\Eloquent;

interface FactoryBuilder
{
    /**
     * Set the amount of models you wish to create / make.
     *
     * @param  int  $amount
     * @return $this
     */
    public function times($amount);

    /**
     * Set the state to be applied to the model.
     *
     * @param  string  $state
     * @return $this
     */
    public function state($state);

    /**
     * Set the states to be applied to the model.
     *
     * @param  array|mixed  $states
     * @return $this
     */
    public function states($states);

    /**
     * Set the database connection on which the model instance should be persisted.
     *
     * @param  string  $name
     * @return $this
     */
    public function connection($name);

    /**
     * Create a model and persist it in the database if requested.
     *
     * @param  array  $attributes
     * @return \Closure
     */
    public function lazy(array $attributes = []);

    /**
     * Create a collection of models and persist them to the database.
     *
     * @param  array  $attributes
     * @return mixed
     */
    public function create(array $attributes = []);

    /**
     * Create a collection of models.
     *
     * @param  array  $attributes
     * @return mixed
     */
    public function make(array $attributes = []);

    /**
     * Create an array of raw attribute arrays.
     *
     * @param  array  $attributes
     * @return mixed
     */
    public function raw(array $attributes = []);

    /**
     * Run after making callbacks on a collection of models.
     *
     * @param  \Illuminate\Support\Collection  $models
     * @return void
     */
    public function callAfterMaking($models);

    /**
     * Run after creating callbacks on a collection of models.
     *
     * @param  \Illuminate\Support\Collection  $models
     * @return void
     */
    public function callAfterCreating($models);
}
