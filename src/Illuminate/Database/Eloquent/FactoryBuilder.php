<?php

namespace Illuminate\Database\Eloquent;

use Closure;
use Illuminate\Support\Arr;
use Faker\Generator as Faker;
use InvalidArgumentException;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Database\Eloquent\Factory\StateManager;
use Illuminate\Database\Eloquent\Factory\RelationRequest;
use Illuminate\Database\Eloquent\Factory\PrototypesModels;
use Illuminate\Database\Eloquent\Factory\BuildsRelationships;
use Illuminate\Database\Eloquent\Factory\NormalizesAttributes;

class FactoryBuilder
{
    use BuildsRelationships,
        Macroable,
        NormalizesAttributes,
        PrototypesModels;

    /**
     * The model being built.
     *
     * @var string
     */
    protected $class;

    /**
     * The database connection on which the model instance should be persisted.
     *
     * @var string
     */
    protected $connection;

    /**
     * The Faker instance for the builder.
     *
     * @var \Faker\Generator
     */
    protected $faker;

    /**
     * The model states.
     *
     * @var StateManager
     */
    protected $stateManager;

    /**
     * Create an new builder instance.
     *
     * @param  string  $class
     * @param  \Illuminate\Database\Eloquent\Factory\StateManager  $stateManager
     * @param  \Faker\Generator  $faker
     * @return void
     */
    public function __construct($class, StateManager $stateManager, Faker $faker)
    {
        $this->class = $class;
        $this->faker = $faker;
        $this->stateManager = $stateManager;
    }

    /**
     * Set the database connection on which the model instance should be persisted.
     *
     * @param  string  $name
     * @return \Illuminate\Database\Eloquent\FactoryBuilder
     */
    public function connection($name)
    {
        $this->connection = $name;

        return $this;
    }

    /**
     * Set the name of definition to be used.
     *
     * @param  string  $name
     * @return $this
     */
    public function definition($name)
    {
        $this->definition = $name;

        if ($name !== 'default' && ! $this->stateManager->definitionExists($this->class, $name)) {
            throw new InvalidArgumentException("Unable to locate factory with name [{$name}] on [{$this->class}].");
        }

        return $this;
    }

    /**
     * Fill attributes on the model.
     *
     * @param  array|callable  $attributes
     * @return $this
     */
    public function fill($attributes)
    {
        array_push($this->attributes, $this->wrapCallable($attributes));

        return $this;
    }

    /**
     * Fill attributes on the pivot model.
     *
     * @param  array|callable  $attributes
     * @return $this
     */
    public function fillPivot($attributes)
    {
        array_push($this->pivotAttributes, $this->wrapCallable($attributes));

        return $this;
    }

    /**
     * Apply the callback given certain odds are met.
     *
     * Example odds: 50, '50%', 1/2
     *
     * @param  mixed  $odds
     * @param  callable  $callback
     * @param  callable|null  $default
     * @return $this
     */
    public function odds($odds, $callback, $default = null)
    {
        if (is_string($odds)) {
            $odds = intval($odds);
        }

        if (is_numeric($odds) && $odds >= 0 && $odds <= 1) {
            $odds = $odds * 100;
        }

        return $this->when(rand(0, 100) <= $odds, $callback, $default);
    }

    /**
     * Apply one or more presets to the model.
     *
     * @param  string  $preset
     * @return $this
     */
    public function preset($preset)
    {
        return $this->presets($preset);
    }

    /**
     * Apply one or more presets to the model.
     *
     * @param  array|mixed  $presets
     * @return $this
     */
    public function presets($presets)
    {
        $this->presets = is_array($presets) ? $presets : func_get_args();

        foreach ($this->presets as $preset) {
            if (! $this->stateManager->presetsExists($this->class, $preset)) {
                throw new InvalidArgumentException("Unable to locate preset with name [{$preset}] on [{$this->class}].");
            }
        }

        return $this;
    }

    /**
     * Set the state to be applied to the model.
     *
     * @param  string  $state
     * @return $this
     */
    public function state($state)
    {
        return $this->states([$state]);
    }

    /**
     * Set the states to be applied to the model.
     *
     * @param  array|mixed  $states
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function states($states)
    {
        $this->states = is_array($states) ? $states : func_get_args();

        foreach ($this->states as $state) {
            if (! $this->stateManager->statesExists($this->class, $state) &&
                ! $this->stateManager->afterCallbackExists($this->class, $state)) {
                throw new InvalidArgumentException("Unable to locate state with name [{$state}] on [{$this->class}].");
            }
        }

        return $this;
    }

    /**
     * Pass the builder to the given callback and then return it.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function tap($callback)
    {
        call_user_func($callback, $this, $this->faker);

        return $this;
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
     * Apply the callback if the value is truthy.
     *
     * @param  bool  $value
     * @param  callable  $callback
     * @param  callable|null  $default
     * @return $this
     */
    public function when($value, $callback, $default = null)
    {
        if ($value) {
            call_user_func($callback, $this, $value);
        } elseif ($default) {
            call_user_func($default, $this, $value);
        }

        return $this;
    }

    /**
     * Build the model with specified relations.
     *
     * @param  mixed  ...$args
     * @return $this
     */
    public function with(...$args)
    {
        if (count($args) === 1 && $args[0] instanceof RelationRequest) {
            return tap($this)->loadRelation($args[0]);
        }

        return tap($this)->loadRelation(
            new RelationRequest($this->class, $this->currentBatch, $this->stateManager, $args)
        );
    }

    /**
     * Build relations in a new batch. Multiple batches can be
     * created on the same relation, so that ie. multiple
     * has-many relations can be configured differently.
     *
     * @param  mixed  ...$args
     * @return $this
     */
    public function andWith(...$args)
    {
        return $this->newBatch()->with(...$args);
    }

    /**
     * Create a model and persist it in the database if requested.
     *
     * @param  array  $attributes
     * @return \Closure
     */
    public function lazy(array $attributes = [])
    {
        return function () use ($attributes) {
            return $this->create($attributes);
        };
    }

    /**
     * Create a collection of models and persist them to the database.
     *
     * @param  array  $attributes
     * @return mixed
     */
    public function create(array $attributes = [])
    {
        $results = $this->make($attributes);

        $this->store($results);

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
        $this->collect($results)->each(function (Model $model) {
            if (! isset($this->connection)) {
                $model->setConnection($model->newQueryWithoutScopes()->getConnection()->getName());
            }

            $this->createBelongsTo($model);

            $model->save();

            $this->createHasMany($model);
            $this->createBelongsToMany($model);
            $this->callAfterCreating($model);
        });
    }

    /**
     * Create a collection of models.
     *
     * @param  array  $attributes
     * @return mixed
     */
    public function make(array $attributes = [])
    {
        return $this->buildResults([new $this->class, 'newCollection'], function () use ($attributes) {
            return $this->makeInstance($attributes);
        });
    }

    /**
     * Create an array of raw attribute arrays.
     *
     * @param  array  $attributes
     * @return mixed
     */
    public function raw(array $attributes = [])
    {
        return $this->buildResults([Arr::class, 'wrap'], function () use ($attributes) {
            return $this->getRawAttributes($attributes);
        });
    }

    /**
     * Build the results to either a single item or collection of items.
     *
     * @param  callable  $collect
     * @param  callable  $item
     * @return mixed
     */
    protected function buildResults($collect, $item)
    {
        if ($this->amount === null) {
            return call_user_func($item);
        }

        if ($this->amount < 1) {
            return call_user_func($collect);
        }

        return call_user_func($collect, array_map($item, range(1, $this->amount)));
    }

    /**
     * Make an instance of the model with the given attributes.
     *
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function makeInstance(array $attributes = [])
    {
        return Model::unguarded(function () use ($attributes) {
            $instance = new $this->class(
                $this->getRawAttributes($attributes)
            );

            if (isset($this->connection)) {
                $instance->setConnection($this->connection);
            }

            return tap($instance, function ($instance) {
                $this->callAfterMaking($instance);
            });
        });
    }

    /**
     * Get a raw attributes array for the model.
     *
     * @param  array  $attributes
     * @return mixed
     */
    protected function getRawAttributes(array $attributes = [])
    {
        $this->applyPresets();

        return collect([$this->stateManager->getDefinition($this->class, $this->definition)])
            ->concat(collect($this->states)->filter()->map(function ($state) {
                return $this->stateManager->getState($this->class, $state) ?: $this->wrapCallable([]);
            }))
            ->concat($this->attributes)
            ->push($this->wrapCallable($attributes))
            ->pipe(function ($callables) use ($attributes) {
                return $this->mergeAndExpandAttributes($callables, $attributes);
            });
    }

    /**
     * Apply the queued presets and finally clear them.
     *
     * @return $this
     */
    protected function applyPresets()
    {
        collect($this->presets)->each(function ($preset) {
            $this->tap($this->stateManager->getPreset($this->class, $preset));
        });

        $this->presets = [];

        return $this;
    }

    /**
     * Run attribute closures, merge resulting attributes, and
     * finally expand to their underlying values.
     *
     * @param  array|\Illuminate\Support\Collection  $attributes
     * @param  array  $inlineAttributes
     * @return array
     */
    protected function mergeAndExpandAttributes($attributes, array $inlineAttributes = [])
    {
        return $this->expandAttributes(
            collect($attributes)->reduce(function ($attributes, $generate) use ($inlineAttributes) {
                return array_merge($attributes, call_user_func($generate, $this->faker, $inlineAttributes));
            }, [])
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
        foreach ($attributes as $key => $attribute) {
            if ($attribute instanceof Closure) {
                $attributes[$key] = $attribute($attributes);
            }

            if ($attribute instanceof static) {
                $attributes[$key] = $attribute->create()->getKey();
            }

            if ($attribute instanceof Model) {
                $attributes[$key] = $attribute->getKey();
            }
        }

        return $attributes;
    }

    /**
     * Run after making callbacks on a collection of models.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    protected function callAfterMaking($model)
    {
        $this->callAfter($this->stateManager->afterMaking, $model);
    }

    /**
     * Run after creating callbacks on a collection of models.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    protected function callAfterCreating($model)
    {
        $this->callAfter($this->stateManager->afterCreating, $model);
    }

    /**
     * Call after callbacks for each state on model.
     *
     * @param  array  $afterCallbacks
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    protected function callAfter(array $afterCallbacks, $model)
    {
        $states = array_merge([$this->definition], $this->states);

        foreach ($states as $state) {
            $callbacks = data_get($afterCallbacks, "{$this->class}.{$state}", []);

            foreach ($callbacks as $callback) {
                call_user_func($callback, $model, $this->faker);
            }
        }
    }
}
