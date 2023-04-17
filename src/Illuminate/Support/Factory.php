<?php

namespace Illuminate\Support;

use Closure;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\ForwardsCalls;
use Illuminate\Support\Traits\Macroable;

/**
 * @template TValue
 */
abstract class Factory
{
    use Conditionable, ForwardsCalls, Macroable {
        __call as macroCall;
    }

    /**
     * The number of items that should be generated.
     *
     * @var int|null
     */
    protected $count;

    /**
     * The state transformations that will be applied to the item.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $states;

    /**
     * The "after making" callbacks that will be applied to the item.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $afterMaking;

    /**
     * Create a new factory instance.
     *
     * @param  int|null  $count
     * @param  \Illuminate\Support\Collection|null  $states
     * @param  \Illuminate\Support\Collection|null  $afterMaking
     * @return void
     */
    public function __construct(
        $count = null,
        ?Collection $states = null,
        ?Collection $afterMaking = null,
    ) {
        $this->count = $count;
        $this->states = $states ?? new Collection;
        $this->afterMaking = $afterMaking ?? new Collection;
    }

    /**
     * Define the item's default state.
     *
     * @return array<mixed>
     */
    abstract public function definition();

    /**
     * Make an instance of the item with the given attributes.
     *
     * @param  array  $expandedAttributes
     * @param  TValue|null  $parent
     * @return TValue
     */
    abstract protected function makeInstance($expandedAttributes, $parent);

    /**
     * Build the appropriate collection for the given items.
     *
     * @param  array<int, TValue>  $items
     * @return \Illuminate\Support\Collection<int, TValue>
     */
    protected function newCollection(array $items)
    {
        return new Collection($items);
    }

    /**
     * Get a new factory instance for the given attributes.
     *
     * @param (callable(array<mixed>): array<mixed>)|array<mixed> $attributes
     * @return static
     */
    public static function new($attributes = [])
    {
        return (new static)->state($attributes)->configure();
    }

    /**
     * Get a new factory instance for the given number of items.
     *
     * @param  int  $count
     * @return static
     */
    public static function times(int $count)
    {
        return static::new()->count($count);
    }

    /**
     * Configure the factory.
     *
     * @return $this
     */
    public function configure()
    {
        return $this;
    }

    /**
     * Build a single instance of the item.
     *
     * @param (callable(array<mixed>): array<mixed>)|array<mixed> $attributes
     * @return TValue
     */
    public function makeOne($attributes = [])
    {
        return $this->count(null)->make($attributes);
    }

    /**
     * Build a collection of items.
     *
     * @param (callable(array<mixed>): array<mixed>)|array<mixed> $attributes
     * @param  TValue|null  $parent
     * @return \Illuminate\Support\Collection<int, TValue>|TValue
     */
    public function make($attributes = [], $parent = null)
    {
        if (! empty($attributes)) {
            return $this->state($attributes)->make([], $parent);
        }

        if ($this->count === null) {
            $instance = $this->makeInstance($this->buildExpandedAttributes($parent), $parent);

            $this->callAfterMaking(collect([$instance]));

            return $instance;
        }

        if ($this->count < 1) {
            return $this->newCollection([]);
        }

        $instances = $this->newCollection(array_map(function () use ($parent) {
            return $this->makeInstance($this->buildExpandedAttributes($parent), $parent);
        }, range(1, $this->count)));

        $this->callAfterMaking($instances);

        return $instances;
    }

    /**
     * Get the states defined on the factory.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getStates()
    {
        return $this->states;
    }

    /**
     * Build a raw attributes array for the item.
     *
     * @param  TValue|null  $parent
     * @return mixed
     */
    protected function buildExpandedAttributes($parent)
    {
        return $this->expandAttributes($this->buildRawAttributes($parent));
    }

    /**
     * Expand the given attribute into its final form.
     *
     * @param  mixed  $attribute
     * @param  string|int  $key
     * @return mixed
     */
    protected function expandAttribute($attribute, $key)
    {
        if ($attribute instanceof self) {
            $attribute = $attribute->make();
        }

        return $attribute;
    }

    /**
     * Expand all attributes to their underlying values.
     *
     * @param  array  $definition
     * @return array
     */
    protected function expandAttributes(array $definition)
    {
        return collect($definition)
            ->map(fn ($attribute, $key) => $this->expandAttribute($attribute, $key))
            ->map(function ($attribute, $key) use (&$definition) {
                if (is_callable($attribute) && ! is_string($attribute) && ! is_array($attribute)) {
                    $attribute = $attribute($definition);
                }

                $attribute = $this->expandAttribute($attribute, $key);

                $definition[$key] = $attribute;

                return $attribute;
            })
            ->all();
    }

    /**
     * Build the raw attributes for the item as an array.
     *
     * @param  TValue|null  $parent
     * @return array
     */
    protected function buildRawAttributes($parent)
    {
        return $this->getStates()->reduce(function ($carry, $state) use ($parent) {
            if ($state instanceof Closure) {
                $state = $state->bindTo($this);
            }

            return array_merge($carry, $state($carry, $parent));
        }, $this->definition());
    }

    /**
     * Add a new state transformation to the item definition.
     *
     * @param  (callable(array<mixed>, TValue|null): array<mixed>)|array<mixed>  $state
     * @return static
     */
    public function state($state)
    {
        return $this->newInstance([
            'states' => $this->states->concat([
                is_callable($state) ? $state : function () use ($state) {
                    return $state;
                },
            ]),
        ]);
    }

    /**
     * Set a single item attribute.
     *
     * @param  string|int  $key
     * @param  mixed  $value
     * @return static
     */
    public function set($key, $value)
    {
        return $this->state([$key => $value]);
    }

    /**
     * Push a new index-based state transformation to the item definition.
     *
     * @param  mixed  $value
     * @return static
     */
    public function push($value)
    {
        return $this->state([$value]);
    }

    /**
     * Add a new sequenced state transformation to the item definition.
     *
     * @param  mixed  ...$sequence
     * @return static
     */
    public function sequence(...$sequence)
    {
        return $this->state(new Sequence(...$sequence));
    }

    /**
     * Add a new sequenced state transformation to the item definition and update the pending creation count to the size of the sequence.
     *
     * @param  array  ...$sequence
     * @return static
     */
    public function forEachSequence(...$sequence)
    {
        return $this->state(new Sequence(...$sequence))->count(count($sequence));
    }

    /**
     * Add a new cross joined sequenced state transformation to the item definition.
     *
     * @param  array  ...$sequence
     * @return static
     */
    public function crossJoinSequence(...$sequence)
    {
        return $this->state(new CrossJoinSequence(...$sequence));
    }

    /**
     * Add a new "after making" callback to the item definition.
     *
     * @param  \Closure(TValue): mixed  $callback
     * @return static
     */
    public function afterMaking(Closure $callback)
    {
        return $this->newInstance(['afterMaking' => $this->afterMaking->concat([$callback])]);
    }

    /**
     * Call the "after making" callbacks for the given item instances.
     *
     * @param  \Illuminate\Support\Collection  $instances
     * @return void
     */
    protected function callAfterMaking(Collection $instances)
    {
        $instances->each(function ($model) {
            $this->afterMaking->each(function ($callback) use ($model) {
                $callback($model);
            });
        });
    }

    /**
     * Specify how many items should be generated.
     *
     * @param  int|null  $count
     * @return static
     */
    public function count(?int $count)
    {
        return $this->newInstance(['count' => $count]);
    }

    /**
     * Create a new instance of the factory builder with the given mutated properties.
     *
     * @param  array  $arguments
     * @return static
     */
    protected function newInstance(array $arguments = [])
    {
        return new static(...[
            'count' => $this->count,
            'states' => $this->states,
            'afterMaking' => $this->afterMaking,
            ...$arguments,
        ]);
    }

    /**
     * Proxy dynamic factory methods onto their proper methods.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }
    }
}
