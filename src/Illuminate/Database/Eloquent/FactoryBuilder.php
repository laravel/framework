<?php

namespace Illuminate\Database\Eloquent;

use Faker\Generator as Faker;
use InvalidArgumentException;
use Illuminate\Support\Traits\Macroable;

class FactoryBuilder
{
    use Macroable;

    /**
     * The model definitions in the container.
     *
     * @var array
     */
    protected $definitions;

    /**
     * The model being built.
     *
     * @var string
     */
    protected $class;

    /**
     * The name of the model being built.
     *
     * @var string
     */
    protected $name = 'default';

    /**
     * The database connection on which the model instance should be persisted.
     *
     * @var string
     */
    protected $connection;

    /**
     * The model states.
     *
     * @var array
     */
    protected $states;

    /**
     * The model after making callbacks.
     *
     * @var array
     */
    protected $afterMaking = [];

    /**
     * The model after creating callbacks.
     *
     * @var array
     */
    protected $afterCreating = [];

    /**
     * The states to apply.
     *
     * @var array
     */
    protected $activeStates = [];

    /**
     * The Faker instance for the builder.
     *
     * @var \Faker\Generator
     */
    protected $faker;

    /**
     * The number of models to build.
     *
     * @var int|null
     */
    protected $amount = null;

    /**
     * Create an new builder instance.
     *
     * @param  string  $class
     * @param  string  $name
     * @param  array  $definitions
     * @param  array  $states
     * @param  array  $afterMaking
     * @param  array  $afterCreating
     * @param  \Faker\Generator  $faker
     * @return void
     */
    public function __construct($class, $name, array $definitions, array $states,
                                array $afterMaking, array $afterCreating, Faker $faker)
    {
        $this->name = $name;
        $this->class = $class;
        $this->faker = $faker;
        $this->states = $states;
        $this->definitions = $definitions;
        $this->afterMaking = $afterMaking;
        $this->afterCreating = $afterCreating;
    }

    /**
     * Set the amount of models you wish to create / make.
     *
     * @param  int  $amount
     * @return $this
     */
    public function times($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Set the states to be applied to the model.
     *
     * @param  array|mixed  $states
     * @return $this
     */
    public function states($states)
    {
        $this->activeStates = is_array($states) ? $states : func_get_args();

        return $this;
    }

    /**
     * Set the database connection on which the model instance should be persisted.
     *
     * @param  string  $name
     * @return $this
     */
    public function connection($name)
    {
        $this->connection = $name;

        return $this;
    }

    /**
     * Create a model and persist it in the database if requested.
     *
     * @param  array  $attributes
     * @param  mixed  ...$extraParams
     * @return \Closure
     */
    public function lazy(array $attributes = [], ...$extraParams)
    {
        return function () use ($attributes, $extraParams) {
            return $this->create($attributes, ...$extraParams);
        };
    }

    /**
     * Create a collection of models and persist them to the database.
     *
     * @param  array  $attributes
     * @param  mixed  ...$extraParams
     * @return mixed
     */
    public function create(array $attributes = [], ...$extraParams)
    {
        $results = $this->make($attributes, ...$extraParams);

        if ($results instanceof Model) {
            $this->store(collect([$results]));

            $this->callAfterCreating(collect([$results]));
        } else {
            $this->store($results);

            $this->callAfterCreating($results);
        }

        return $results;
    }

    /**
     * Set the connection name on the results and store them.
     *
     * @param  \Illuminate\Support\Collection  $results
     * @return void
     */
    protected function store($results)
    {
        $results->each(function ($model) {
            if (! isset($this->connection)) {
                $model->setConnection($model->newQueryWithoutScopes()->getConnection()->getName());
            }

            $model->save();
        });
    }

    /**
     * Create a collection of models.
     *
     * @param  array  $attributes
     * @param  mixed  ...$extraParams
     * @return mixed
     */
    public function make(array $attributes = [], ...$extraParams)
    {
        if ($this->amount === null) {
            return tap($this->makeInstance($attributes, $extraParams), function ($instance) {
                $this->callAfterMaking(collect([$instance]));
            });
        }

        if ($this->amount < 1) {
            return (new $this->class)->newCollection();
        }

        $instances = (new $this->class)->newCollection(array_map(function () use ($attributes, $extraParams) {
            return $this->makeInstance($attributes, $extraParams);
        }, range(1, $this->amount)));

        $this->callAfterMaking($instances);

        return $instances;
    }

    /**
     * Create an array of raw attribute arrays.
     *
     * @param  array  $attributes
     * @param  mixed  ...$extraParams
     * @return mixed
     */
    public function raw(array $attributes = [], ...$extraParams)
    {
        if ($this->amount === null) {
            return $this->getRawAttributes($attributes, $extraParams);
        }

        if ($this->amount < 1) {
            return [];
        }

        return array_map(function () use ($attributes, $extraParams) {
            return $this->getRawAttributes($attributes, $extraParams);
        }, range(1, $this->amount));
    }

    /**
     * Get a raw attributes array for the model.
     *
     * @param  array  $attributes
     * @param  array  $extraParams
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    protected function getRawAttributes(array $attributes = [], array $extraParams = [])
    {
        if (! isset($this->definitions[$this->class][$this->name])) {
            throw new InvalidArgumentException("Unable to locate factory with name [{$this->name}] [{$this->class}].");
        }

        $definition = call_user_func(
            $this->definitions[$this->class][$this->name],
            $this->faker, $attributes, ...$extraParams
        );

        return $this->expandAttributes(
            array_merge($this->applyStates($definition, $attributes, $extraParams), $attributes)
        );
    }

    /**
     * Make an instance of the model with the given attributes.
     *
     * @param  array  $attributes
     * @param  array  $extraParams
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function makeInstance(array $attributes = [], array $extraParams = [])
    {
        return Model::unguarded(function () use ($attributes, $extraParams) {
            $instance = new $this->class(
                $this->getRawAttributes($attributes, $extraParams)
            );

            if (isset($this->connection)) {
                $instance->setConnection($this->connection);
            }

            return $instance;
        });
    }

    /**
     * Apply the active states to the model definition array.
     *
     * @param  array  $definition
     * @param  array  $attributes
     * @param  array  $extraParams
     * @return array
     */
    protected function applyStates(array $definition, array $attributes = [], array $extraParams = [])
    {
        foreach ($this->activeStates as $state) {
            if (! isset($this->states[$this->class][$state])) {
                if ($this->stateHasAfterCallback($state)) {
                    continue;
                }

                throw new InvalidArgumentException("Unable to locate [{$state}] state for [{$this->class}].");
            }

            $definition = array_merge(
                $definition,
                $this->stateAttributes($state, $attributes, $extraParams)
            );
        }

        return $definition;
    }

    /**
     * Get the state attributes.
     *
     * @param  string  $state
     * @param  array  $attributes
     * @param  array  $extraParams
     * @return array
     */
    protected function stateAttributes($state, array $attributes, array $extraParams = [])
    {
        $stateAttributes = $this->states[$this->class][$state];

        if (! is_callable($stateAttributes)) {
            return $stateAttributes;
        }

        return call_user_func(
            $stateAttributes,
            $this->faker, $attributes, ...$extraParams
        );
    }

    /**
     * Expand all attributes to their underlying values.
     *
     * @param  array  $attributes
     * @return array
     */
    protected function expandAttributes(array $attributes)
    {
        foreach ($attributes as &$attribute) {
            if (is_callable($attribute) && ! is_string($attribute) && ! is_array($attribute)) {
                $attribute = $attribute($attributes);
            }

            if ($attribute instanceof static) {
                $attribute = $attribute->create()->getKey();
            }

            if ($attribute instanceof Model) {
                $attribute = $attribute->getKey();
            }
        }

        return $attributes;
    }

    /**
     * Run after making callbacks on a collection of models.
     *
     * @param  \Illuminate\Support\Collection  $models
     * @return void
     */
    public function callAfterMaking($models)
    {
        $this->callAfter($this->afterMaking, $models);
    }

    /**
     * Run after creating callbacks on a collection of models.
     *
     * @param  \Illuminate\Support\Collection  $models
     * @return void
     */
    public function callAfterCreating($models)
    {
        $this->callAfter($this->afterCreating, $models);
    }

    /**
     * Call after callbacks for each model and state.
     *
     * @param  array  $afterCallbacks
     * @param  \Illuminate\Support\Collection  $models
     * @return void
     */
    protected function callAfter(array $afterCallbacks, $models)
    {
        $states = array_merge([$this->name], $this->activeStates);

        $models->each(function ($model) use ($states, $afterCallbacks) {
            foreach ($states as $state) {
                $this->callAfterCallbacks($afterCallbacks, $model, $state);
            }
        });
    }

    /**
     * Call after callbacks for each model and state.
     *
     * @param  array  $afterCallbacks
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $state
     * @return void
     */
    protected function callAfterCallbacks(array $afterCallbacks, $model, $state)
    {
        if (! isset($afterCallbacks[$this->class][$state])) {
            return;
        }

        foreach ($afterCallbacks[$this->class][$state] as $callback) {
            $callback($model, $this->faker);
        }
    }

    /**
     * Determine if the given state has an "after" callback.
     *
     * @param  string  $state
     * @return bool
     */
    protected function stateHasAfterCallback($state)
    {
        return isset($this->afterMaking[$this->class][$state]) ||
               isset($this->afterCreating[$this->class][$state]);
    }
}
