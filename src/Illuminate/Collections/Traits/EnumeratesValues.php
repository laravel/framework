<?php

namespace Illuminate\Support\Traits;

use BackedEnum;
use CachingIterator;
use Closure;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Illuminate\Support\HigherOrderCollectionProxy;
use JsonSerializable;
use UnexpectedValueException;
use UnitEnum;

use function Illuminate\Support\enum_value;

/**
 * @template TKey of array-key
 *
 * @template TValue
 *
 * @property-read HigherOrderCollectionProxy<TKey, TValue> $average
 * @property-read HigherOrderCollectionProxy<TKey, TValue> $avg
 * @property-read HigherOrderCollectionProxy<TKey, TValue> $contains
 * @property-read HigherOrderCollectionProxy<TKey, TValue> $doesntContain
 * @property-read HigherOrderCollectionProxy<TKey, TValue> $each
 * @property-read HigherOrderCollectionProxy<TKey, TValue> $every
 * @property-read HigherOrderCollectionProxy<TKey, TValue> $filter
 * @property-read HigherOrderCollectionProxy<TKey, TValue> $first
 * @property-read HigherOrderCollectionProxy<TKey, TValue> $flatMap
 * @property-read HigherOrderCollectionProxy<TKey, TValue> $groupBy
 * @property-read HigherOrderCollectionProxy<TKey, TValue> $hasMany
 * @property-read HigherOrderCollectionProxy<TKey, TValue> $hasSole
 * @property-read HigherOrderCollectionProxy<TKey, TValue> $keyBy
 * @property-read HigherOrderCollectionProxy<TKey, TValue> $last
 * @property-read HigherOrderCollectionProxy<TKey, TValue> $map
 * @property-read HigherOrderCollectionProxy<TKey, TValue> $max
 * @property-read HigherOrderCollectionProxy<TKey, TValue> $min
 * @property-read HigherOrderCollectionProxy<TKey, TValue> $partition
 * @property-read HigherOrderCollectionProxy<TKey, TValue> $percentage
 * @property-read HigherOrderCollectionProxy<TKey, TValue> $reject
 * @property-read HigherOrderCollectionProxy<TKey, TValue> $skipUntil
 * @property-read HigherOrderCollectionProxy<TKey, TValue> $skipWhile
 * @property-read HigherOrderCollectionProxy<TKey, TValue> $some
 * @property-read HigherOrderCollectionProxy<TKey, TValue> $sortBy
 * @property-read HigherOrderCollectionProxy<TKey, TValue> $sortByDesc
 * @property-read HigherOrderCollectionProxy<TKey, TValue> $sum
 * @property-read HigherOrderCollectionProxy<TKey, TValue> $takeUntil
 * @property-read HigherOrderCollectionProxy<TKey, TValue> $takeWhile
 * @property-read HigherOrderCollectionProxy<TKey, TValue> $unique
 * @property-read HigherOrderCollectionProxy<TKey, TValue> $unless
 * @property-read HigherOrderCollectionProxy<TKey, TValue> $until
 * @property-read HigherOrderCollectionProxy<TKey, TValue> $when
 */
trait EnumeratesValues<TKey = mixed, TValue = mixed>
{
    use Conditionable;

    /**
     * Indicates that the object's string representation should be escaped when __toString is invoked.
     *
     * @var bool
     */
    protected $escapeWhenCastingToString = false;

    /**
     * The methods that can be proxied.
     *
     * @var array<int, string>
     */
    protected static $proxies = [
        'average',
        'avg',
        'contains',
        'doesntContain',
        'each',
        'every',
        'filter',
        'first',
        'flatMap',
        'groupBy',
        'hasMany',
        'hasSole',
        'keyBy',
        'last',
        'map',
        'max',
        'min',
        'partition',
        'percentage',
        'reject',
        'skipUntil',
        'skipWhile',
        'some',
        'sortBy',
        'sortByDesc',
        'sum',
        'takeUntil',
        'takeWhile',
        'unique',
        'unless',
        'until',
        'when',
    ];

    /**
     * Create a new collection instance if the value isn't one already.
     *
     * @template TMakeKey of array-key
     * @template TMakeValue
     *
     * @param  \Illuminate\Contracts\Support\Arrayable<TMakeKey, TMakeValue>|iterable<TMakeKey, TMakeValue>|null  $items
     * @return static<TMakeKey, TMakeValue>
     */
    public static function make<TMakeKey = mixed, TMakeValue = mixed>($items = [], ...$args): static<TMakeKey, TMakeValue>
    {
        return new static($items, ...$args);
    }

    /**
     * Wrap the given value in a collection if applicable.
     *
     * @template TWrapValue
     *
     * @param  iterable<array-key, TWrapValue>|TWrapValue  $value
     * @return static<array-key, TWrapValue>
     */
    public static function wrap<TWrapValue = mixed>($value, ...$args): static<mixed, TWrapValue>
    {
        return $value instanceof Enumerable
            ? new static($value, ...$args)
            : new static(Arr::wrap($value), ...$args);
    }

    /**
     * Get the underlying items from the given collection if applicable.
     *
     * @template TUnwrapKey of array-key
     * @template TUnwrapValue
     *
     * @param  array<TUnwrapKey, TUnwrapValue>|static<TUnwrapKey, TUnwrapValue>  $value
     * @return array<TUnwrapKey, TUnwrapValue>
     */
    public static function unwrap<TUnwrapKey = mixed, TUnwrapValue = mixed>($value)
    {
        return $value instanceof Enumerable ? $value->all() : $value;
    }

    /**
     * Create a new instance with no items.
     *
     * @return static
     */
    public static function empty<TEmptyKey = mixed, TEmptyValue = mixed>(...$args): static<TEmptyKey, TEmptyValue>
    {
        return new static([], ...$args);
    }

    /**
     * Create a new collection by invoking the callback a given amount of times.
     *
     * @template TTimesValue
     *
     * @param  int  $number
     * @param  (callable(int): TTimesValue)|null  $callback
     * @return static<int, TTimesValue>
     */
    public static function times<TTimesValue = mixed>($number, ?callable $callback = null, ...$args): static<int, TTimesValue>
    {
        if ($number < 1) {
            return new static([], ...$args);
        }

        return static::range(1, $number, 1, ...$args)
            ->unless($callback == null)
            ->map($callback);
    }

    /**
     * Create a new collection by decoding a JSON string.
     *
     * @param  string  $json
     * @param  int  $depth
     * @param  int  $flags
     * @return static<TKey, TValue>
     */
    public static function fromJson(string $json, int $depth = 512, int $flags = 0, ...$args)
    {
        return new static(json_decode($json, true, $depth, $flags), ...$args);
    }

    /**
     * Get the average value of a given key.
     *
     * @param  (callable(TValue): float|int)|string|null  $callback
     * @return float|int|null
     */
    public function avg($callback = null): float|int|null
    {
        $callback = $this->valueRetriever($callback);

        $reduced = $this->reduce(static function (&$reduce, $value) use ($callback) {
            if (! is_null($resolved = $callback($value))) {
                $reduce[0] += $resolved;
                $reduce[1]++;
            }

            return $reduce;
        }, [0, 0]);

        return $reduced[1] ? $reduced[0] / $reduced[1] : null;
    }

    /**
     * Alias for the "avg" method.
     *
     * @param  (callable(TValue): float|int)|string|null  $callback
     * @return float|int|null
     */
    public function average($callback = null): float|int|null
    {
        return $this->avg($callback);
    }

    /**
     * Alias for the "contains" method.
     *
     * @param  (callable(TValue, TKey): bool)|TValue|string  $key
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return bool
     */
    public function some($key, $operator = null, $value = null): bool
    {
        return $this->contains(...func_get_args());
    }

    /**
     * Dump the given arguments and terminate execution.
     *
     * @param  mixed  ...$args
     * @return never
     */
    public function dd(...$args): never
    {
        dd($this->all(), ...$args);
    }

    /**
     * Dump the items.
     *
     * @param  mixed  ...$args
     * @return $this
     */
    public function dump(...$args): static
    {
        dump($this->all(), ...$args);

        return $this;
    }

    /**
     * Execute a callback over each item.
     *
     * @param  callable(TValue, TKey): mixed  $callback
     * @return $this
     */
    public function each(callable $callback): static
    {
        foreach ($this as $key => $item) {
            if ($callback($item, $key) === false) {
                break;
            }
        }

        return $this;
    }

    /**
     * Execute a callback over each nested chunk of items.
     *
     * @param  callable(...mixed): mixed  $callback
     * @return static
     */
    public function eachSpread(callable $callback): static
    {
        return $this->each(function ($chunk, $key) use ($callback) {
            $chunk[] = $key;

            return $callback(...$chunk);
        });
    }

    /**
     * Determine if all items pass the given truth test.
     *
     * @param  (callable(TValue, TKey): bool)|TValue|string  $key
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return bool
     */
    public function every($key, $operator = null, $value = null): bool
    {
        if (func_num_args() === 1) {
            $callback = $this->valueRetriever($key);

            foreach ($this as $k => $v) {
                if (! $callback($v, $k)) {
                    return false;
                }
            }

            return true;
        }

        return $this->every($this->operatorForWhere(...func_get_args()));
    }

    /**
     * Get the first item by the given key value pair.
     *
     * @param  callable|string  $key
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return TValue|null
     */
    public function firstWhere($key, $operator = null, $value = null): TValue|null
    {
        return $this->first($this->operatorForWhere(...func_get_args()));
    }

    /**
     * Determine if the collection contains multiple items, optionally matching the given criteria.
     *
     * @param  (callable(TValue, TKey): bool)|string|null  $key
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return bool
     */
    public function hasMany($key = null, $operator = null, $value = null): bool
    {
        $filter = func_num_args() > 1
            ? $this->operatorForWhere(...func_get_args())
            : $key;

        return $this
            ->unless($filter == null)
            ->filter($filter)
            ->take(2)
            ->count() === 2;
    }

    /**
     * Get a single key's value from the first matching item in the collection.
     *
     * @template TValueDefault
     *
     * @param  string  $key
     * @param  TValueDefault|(\Closure(): TValueDefault)  $default
     * @return TValue|TValueDefault
     */
    public function value<TValueDefault = mixed>(string $key, $default = null): TValue|TValueDefault
    {
        $value = $this->first(function ($target) use ($key) {
            return data_has($target, $key);
        });

        return data_get($value, $key, $default);
    }

    /**
     * Ensure that every item in the collection is of the expected type.
     *
     * @template TEnsureOfType
     *
     * @param  class-string<TEnsureOfType>|array<array-key, class-string<TEnsureOfType>>|'string'|'int'|'float'|'bool'|'array'|'null'  $type
     * @return static<TKey, TEnsureOfType>
     *
     * @throws \UnexpectedValueException
     */
    public function ensure<TEnsureOfType = mixed>($type): static<TKey, TEnsureOfType>
    {
        $allowedTypes = is_array($type) ? $type : [$type];

        return $this->each(function ($item, $index) use ($allowedTypes) {
            $itemType = get_debug_type($item);

            foreach ($allowedTypes as $allowedType) {
                if ($itemType === $allowedType || $item instanceof $allowedType) {
                    return true;
                }
            }

            throw new UnexpectedValueException(
                sprintf("Collection should only include [%s] items, but '%s' found at position %d.", implode(', ', $allowedTypes), $itemType, $index)
            );
        });
    }

    /**
     * Determine if the collection is not empty.
     *
     * @phpstan-assert-if-true TValue $this->first()
     * @phpstan-assert-if-true TValue $this->last()
     *
     * @phpstan-assert-if-false null $this->first()
     * @phpstan-assert-if-false null $this->last()
     *
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    /**
     * Run a map over each nested chunk of items.
     *
     * @template TMapSpreadValue
     *
     * @param  callable(mixed...): TMapSpreadValue  $callback
     * @return static<TKey, TMapSpreadValue>
     */
    public function mapSpread<TMapSpreadValue = mixed>(callable $callback): static<TKey, TMapSpreadValue>
    {
        return $this->map(function ($chunk, $key) use ($callback) {
            $chunk[] = $key;

            return $callback(...$chunk);
        });
    }

    /**
     * Run a grouping map over the items.
     *
     * The callback should return an associative array with a single key/value pair.
     *
     * @template TMapToGroupsKey of array-key
     * @template TMapToGroupsValue
     *
     * @param  callable(TValue, TKey): array<TMapToGroupsKey, TMapToGroupsValue>  $callback
     * @return static<TMapToGroupsKey, static<int, TMapToGroupsValue>>
     */
    public function mapToGroups<TMapToGroupsKey = mixed, TMapToGroupsValue = mixed>(callable $callback): static<TMapToGroupsKey, static<int, TMapToGroupsValue>>
    {
        $groups = $this->mapToDictionary($callback);

        return $groups->map($this->make(...));
    }

    /**
     * Map a collection and flatten the result by a single level.
     *
     * @template TFlatMapKey of array-key
     * @template TFlatMapValue
     *
     * @param  callable(TValue, TKey): (\Illuminate\Support\Collection<TFlatMapKey, TFlatMapValue>|array<TFlatMapKey, TFlatMapValue>)  $callback
     * @return static<TFlatMapKey, TFlatMapValue>
     */
    public function flatMap<TFlatMapKey = mixed, TFlatMapValue = mixed>(callable $callback): static<TFlatMapKey, TFlatMapValue>
    {
        return $this->map($callback)->collapse();
    }

    /**
     * Map the values into a new class.
     *
     * @template TMapIntoValue
     *
     * @param  class-string<TMapIntoValue>  $class
     * @return static<TKey, TMapIntoValue>
     */
    public function mapInto<TMapIntoValue = mixed>($class): static<TKey, TMapIntoValue>
    {
        if (is_subclass_of($class, BackedEnum::class)) {
            return $this->map(fn ($value, $key) => $class::from($value));
        }

        return $this->map(fn ($value, $key) => new $class($value, $key));
    }

    /**
     * Get the min value of a given key.
     *
     * @template TMinResult = mixed
     *
     * @param  (callable(TValue): TMinResult)|string|null  $callback
     * @return ($callback is callable ? ?TMinResult : ($callback is null ? ?TValue : mixed))
     */
    public function min($callback = null)
    {
        $callback = $this->valueRetriever($callback);

        return $this->map(fn ($value) => $callback($value))
            ->reject(fn ($value) => is_null($value))
            ->reduce(fn ($result, $value) => is_null($result) || $value < $result ? $value : $result);
    }

    /**
     * Get the max value of a given key.
     *
     * @template TMaxResult = mixed
     *
     * @param  (callable(TValue): TMaxResult)|string|null  $callback
     * @return ($callback is callable ? ?TMaxResult : ($callback is null ? ?TValue : mixed))
     */
    public function max($callback = null)
    {
        $callback = $this->valueRetriever($callback);

        return $this->reject(fn ($value) => is_null($value))->reduce(function ($result, $item) use ($callback) {
            $value = $callback($item);

            return is_null($result) || $value > $result ? $value : $result;
        });
    }

    /**
     * "Paginate" the collection by slicing it into a smaller collection.
     *
     * @param  int  $page
     * @param  int  $perPage
     * @return static
     */
    public function forPage(int $page, int $perPage): static
    {
        $offset = max(0, ($page - 1) * $perPage);

        return $this->slice($offset, $perPage);
    }

    /**
     * Partition the collection into two arrays using the given callback or key.
     *
     * @param  (callable(TValue, TKey): bool)|TValue|string  $key
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return static<int<0, 1>, static<TKey, TValue>>
     */
    public function partition($key, $operator = null, $value = null)
    {
        $callback = func_num_args() === 1
            ? $this->valueRetriever($key)
            : $this->operatorForWhere(...func_get_args());

        [$passed, $failed] = Arr::partition($this->getIterator(), $callback);

        return $this->newInstance([$this->newInstance($passed), $this->newInstance($failed)]);
    }

    /**
     * Calculate the percentage of items that pass a given truth test.
     *
     * @param  (callable(TValue, TKey): bool)  $callback
     * @param  int  $precision
     * @return float|null
     */
    public function percentage(callable $callback, int $precision = 2): float|null
    {
        if ($this->isEmpty()) {
            return null;
        }

        return round(
            $this->filter($callback)->count() / $this->count() * 100,
            $precision
        );
    }

    /**
     * Get the sum of the given values.
     *
     * @template TReturnType
     *
     * @param  (callable(TValue, TKey): TReturnType)|string|null  $callback
     * @return ($callback is callable ? TReturnType : mixed)
     */
    public function sum($callback = null)
    {
        $callback = is_null($callback)
            ? $this->identity()
            : $this->valueRetriever($callback);

        return $this->reduce(fn ($result, $item, $key) => $result + $callback($item, $key), 0);
    }

    /**
     * Apply the callback if the collection is empty.
     *
     * @template TWhenEmptyReturnType
     *
     * @param  (callable($this): TWhenEmptyReturnType)  $callback
     * @param  (callable($this): TWhenEmptyReturnType)|null  $default
     * @return $this|TWhenEmptyReturnType
     */
    public function whenEmpty<TWhenEmptyReturnType = mixed>(callable $callback, ?callable $default = null): static|TWhenEmptyReturnType
    {
        return $this->when($this->isEmpty(), $callback, $default);
    }

    /**
     * Apply the callback if the collection is not empty.
     *
     * @template TWhenNotEmptyReturnType
     *
     * @param  callable($this): TWhenNotEmptyReturnType  $callback
     * @param  (callable($this): TWhenNotEmptyReturnType)|null  $default
     * @return $this|TWhenNotEmptyReturnType
     */
    public function whenNotEmpty<TWhenNotEmptyReturnType = mixed>(callable $callback, ?callable $default = null): static|TWhenNotEmptyReturnType
    {
        return $this->when($this->isNotEmpty(), $callback, $default);
    }

    /**
     * Apply the callback unless the collection is empty.
     *
     * @template TUnlessEmptyReturnType
     *
     * @param  callable($this): TUnlessEmptyReturnType  $callback
     * @param  (callable($this): TUnlessEmptyReturnType)|null  $default
     * @return $this|TUnlessEmptyReturnType
     */
    public function unlessEmpty<TUnlessEmptyReturnType = mixed>(callable $callback, ?callable $default = null): static|TUnlessEmptyReturnType
    {
        return $this->whenNotEmpty($callback, $default);
    }

    /**
     * Apply the callback unless the collection is not empty.
     *
     * @template TUnlessNotEmptyReturnType
     *
     * @param  callable($this): TUnlessNotEmptyReturnType  $callback
     * @param  (callable($this): TUnlessNotEmptyReturnType)|null  $default
     * @return $this|TUnlessNotEmptyReturnType
     */
    public function unlessNotEmpty<TUnlessNotEmptyReturnType = mixed>(callable $callback, ?callable $default = null): static|TUnlessNotEmptyReturnType
    {
        return $this->whenEmpty($callback, $default);
    }

    /**
     * Filter items by the given key value pair.
     *
     * @param  callable|string  $key
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return static
     */
    public function where($key, $operator = null, $value = null): static
    {
        return $this->filter($this->operatorForWhere(...func_get_args()));
    }

    /**
     * Filter items where the value for the given key is null.
     *
     * @param  string|null  $key
     * @return static
     */
    public function whereNull(?string $key = null): static
    {
        return $this->whereStrict($key, null);
    }

    /**
     * Filter items where the value for the given key is not null.
     *
     * @param  string|null  $key
     * @return static
     */
    public function whereNotNull(?string $key = null): static
    {
        return $this->where($key, '!==', null);
    }

    /**
     * Filter items by the given key value pair using strict comparison.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return static
     */
    public function whereStrict(?string $key, $value): static
    {
        return $this->where($key, '===', $value);
    }

    /**
     * Filter items by the given key value pair.
     *
     * @param  string  $key
     * @param  \Illuminate\Contracts\Support\Arrayable|iterable  $values
     * @param  bool  $strict
     * @return static
     */
    public function whereIn(string $key, Arrayable|iterable $values, bool $strict = false): static
    {
        $values = $this->getArrayableItems($values);

        return $this->filter(fn ($item) => in_array(data_get($item, $key), $values, $strict));
    }

    /**
     * Filter items by the given key value pair using strict comparison.
     *
     * @param  string  $key
     * @param  \Illuminate\Contracts\Support\Arrayable|iterable  $values
     * @return static
     */
    public function whereInStrict(string $key, Arrayable|iterable $values): static
    {
        return $this->whereIn($key, $values, true);
    }

    /**
     * Filter items such that the value of the given key is between the given values.
     *
     * @param  string  $key
     * @param  \Illuminate\Contracts\Support\Arrayable|iterable  $values
     * @return static
     */
    public function whereBetween(string $key, Arrayable|iterable $values): static
    {
        return $this->where($key, '>=', reset($values))->where($key, '<=', end($values));
    }

    /**
     * Filter items such that the value of the given key is not between the given values.
     *
     * @param  string  $key
     * @param  \Illuminate\Contracts\Support\Arrayable|iterable  $values
     * @return static
     */
    public function whereNotBetween(string $key, Arrayable|iterable $values): static
    {
        return $this->filter(
            fn ($item) => data_get($item, $key) < reset($values) || data_get($item, $key) > end($values)
        );
    }

    /**
     * Filter items by the given key value pair.
     *
     * @param  string  $key
     * @param  \Illuminate\Contracts\Support\Arrayable|iterable  $values
     * @param  bool  $strict
     * @return static
     */
    public function whereNotIn(string $key, Arrayable|iterable $values, bool $strict = false): static
    {
        $values = $this->getArrayableItems($values);

        return $this->reject(fn ($item) => in_array(data_get($item, $key), $values, $strict));
    }

    /**
     * Filter items by the given key value pair using strict comparison.
     *
     * @param  string  $key
     * @param  \Illuminate\Contracts\Support\Arrayable|iterable  $values
     * @return static
     */
    public function whereNotInStrict(string $key, Arrayable|iterable $values): static
    {
        return $this->whereNotIn($key, $values, true);
    }

    /**
     * Filter the items, removing any items that don't match the given type(s).
     *
     * @template TWhereInstanceOf
     *
     * @param  class-string<TWhereInstanceOf>|array<array-key, class-string<TWhereInstanceOf>>  $type
     * @return static<TKey, TWhereInstanceOf>
     */
    public function whereInstanceOf<TWhereInstanceOf = mixed>($type): static<TKey, TWhereInstanceOf>
    {
        return $this->filter(function ($value) use ($type) {
            if (is_array($type)) {
                foreach ($type as $classType) {
                    if ($value instanceof $classType) {
                        return true;
                    }
                }

                return false;
            }

            return $value instanceof $type;
        });
    }

    /**
     * Pass the collection to the given callback and return the result.
     *
     * @template TPipeReturnType
     *
     * @param  callable($this): TPipeReturnType  $callback
     * @return TPipeReturnType
     */
    public function pipe<TPipeReturnType = mixed>(callable $callback): TPipeReturnType
    {
        return $callback($this);
    }

    /**
     * Pass the collection into a new class.
     *
     * @template TPipeIntoValue
     *
     * @param  class-string<TPipeIntoValue>  $class
     * @return TPipeIntoValue
     */
    public function pipeInto<TPipeIntoValue = mixed>($class): TPipeIntoValue
    {
        return new $class($this);
    }

    /**
     * Pass the collection through a series of callable pipes and return the result.
     *
     * @param  array<callable>  $callbacks
     * @return mixed
     */
    public function pipeThrough(array $callbacks): mixed
    {
        return (new Collection($callbacks))->reduce(
            fn ($carry, $callback) => $callback($carry),
            $this,
        );
    }

    /**
     * Reduce the collection to a single value.
     *
     * @template TReduceInitial
     * @template TReduceReturnType
     *
     * @param  callable(TReduceInitial|TReduceReturnType, TValue, TKey): TReduceReturnType  $callback
     * @param  TReduceInitial  $initial
     * @return TReduceInitial|TReduceReturnType
     */
    public function reduce<TReduceInitial = mixed, TReduceReturnType = mixed>(callable $callback, TReduceInitial $initial = null): TReduceInitial|TReduceReturnType
    {
        $result = $initial;

        foreach ($this as $key => $value) {
            $result = $callback($result, $value, $key);
        }

        return $result;
    }

    /**
     * Reduce the collection to multiple aggregate values.
     *
     * @param  callable  $callback
     * @param  mixed  ...$initial
     * @return array
     *
     * @throws \UnexpectedValueException
     */
    public function reduceSpread(callable $callback, mixed ...$initial): array
    {
        $result = $initial;

        foreach ($this as $key => $value) {
            $result = call_user_func_array($callback, array_merge($result, [$value, $key]));

            if (! is_array($result)) {
                throw new UnexpectedValueException(sprintf(
                    "%s::reduceSpread expects reducer to return an array, but got a '%s' instead.",
                    class_basename(static::class), gettype($result)
                ));
            }
        }

        return $result;
    }

    /**
     * Reduce an associative collection to a single value.
     *
     * @template TReduceWithKeysInitial
     * @template TReduceWithKeysReturnType
     *
     * @param  callable(TReduceWithKeysInitial|TReduceWithKeysReturnType, TValue, TKey): TReduceWithKeysReturnType  $callback
     * @param  TReduceWithKeysInitial  $initial
     * @return TReduceWithKeysInitial|TReduceWithKeysReturnType
     */
    public function reduceWithKeys<TReduceWithKeysInitial = mixed, TReduceWithKeysReturnType = mixed>(callable $callback, TReduceWithKeysInitial $initial = null): TReduceWithKeysInitial|TReduceWithKeysReturnType
    {
        return $this->reduce($callback, $initial);
    }

    /**
     * Create a collection of all elements that do not pass a given truth test.
     *
     * @param  (callable(TValue, TKey): bool)|bool|TValue  $callback
     * @return static
     */
    public function reject($callback = true): static
    {
        $useAsCallable = $this->useAsCallable($callback);

        return $this->filter(function ($value, $key) use ($callback, $useAsCallable) {
            return $useAsCallable
                ? ! $callback($value, $key)
                : $value != $callback;
        });
    }

    /**
     * Pass the collection to the given callback and then return it.
     *
     * @param  callable($this): mixed  $callback
     * @return $this
     */
    public function tap(callable $callback): static
    {
        $callback($this);

        return $this;
    }

    /**
     * Return only unique items from the collection array.
     *
     * @param  (callable(TValue, TKey): mixed)|string|null  $key
     * @param  bool  $strict
     * @return static
     */
    public function unique($key = null, bool $strict = false): static
    {
        $callback = $this->valueRetriever($key);

        $exists = [];

        return $this->reject(function ($item, $key) use ($callback, $strict, &$exists) {
            if (in_array($id = $callback($item, $key), $exists, $strict)) {
                return true;
            }

            $exists[] = $id;
        });
    }

    /**
     * Return only unique items from the collection array using strict comparison.
     *
     * @param  (callable(TValue, TKey): mixed)|string|null  $key
     * @return static
     */
    public function uniqueStrict($key = null): static
    {
        return $this->unique($key, true);
    }

    /**
     * Collect the values into a collection.
     *
     * @return \Illuminate\Support\Collection<TKey, TValue>
     */
    public function collect(): \Illuminate\Support\Collection<TKey, TValue>
    {
        return new Collection($this->all());
    }

    /**
     * Get the collection of items as a plain array.
     *
     * @return array<TKey, mixed>
     */
    public function toArray(): array
    {
        return $this->map(fn ($value) => $value instanceof Arrayable ? $value->toArray() : $value)->all();
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array<TKey, mixed>
     */
    public function jsonSerialize(): array
    {
        return array_map(function ($value) {
            return match (true) {
                $value instanceof JsonSerializable => $value->jsonSerialize(),
                $value instanceof Jsonable => json_decode($value->toJson(), true),
                $value instanceof Arrayable => $value->toArray(),
                default => $value,
            };
        }, $this->all());
    }

    /**
     * Get the collection of items as JSON.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Get the collection of items as pretty print formatted JSON.
     *
     * @param  int  $options
     * @return string
     */
    public function toPrettyJson(int $options = 0): string
    {
        return $this->toJson(JSON_PRETTY_PRINT | $options);
    }

    /**
     * Get a CachingIterator instance.
     *
     * @param  int  $flags
     * @return \CachingIterator
     */
    public function getCachingIterator(int $flags = CachingIterator::CALL_TOSTRING): \CachingIterator
    {
        return new CachingIterator($this->getIterator(), $flags);
    }

    /**
     * Convert the collection to its string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->escapeWhenCastingToString
            ? e($this->toJson())
            : $this->toJson();
    }

    /**
     * Indicate that the model's string representation should be escaped when __toString is invoked.
     *
     * @param  bool  $escape
     * @return $this
     */
    public function escapeWhenCastingToString(bool $escape = true): static
    {
        $this->escapeWhenCastingToString = $escape;

        return $this;
    }

    /**
     * Add a method to the list of proxied methods.
     *
     * @param  string  $method
     * @return void
     */
    public static function proxy(string $method): void
    {
        static::$proxies[] = $method;
    }

    /**
     * Dynamically access collection proxies.
     *
     * @param  string  $key
     * @return mixed
     *
     * @throws \Exception
     */
    public function __get(string $key): mixed
    {
        if (! in_array($key, static::$proxies)) {
            throw new Exception("Property [{$key}] does not exist on this collection instance.");
        }

        return new HigherOrderCollectionProxy($this, $key);
    }

    /**
     * Results array of items from Collection or Arrayable.
     *
     * @param  mixed  $items
     * @return array<TKey, TValue>
     */
    protected function getArrayableItems(mixed $items): array
    {
        return is_null($items) || is_scalar($items) || $items instanceof UnitEnum
            ? Arr::wrap($items)
            : Arr::from($items);
    }

    /**
     * Get an operator checker callback.
     *
     * @param  callable|string  $key
     * @param  string|null  $operator
     * @param  mixed  $value
     * @return \Closure
     */
    protected function operatorForWhere($key, $operator = null, $value = null)
    {
        if ($this->useAsCallable($key)) {
            return $key;
        }

        if (func_num_args() === 1) {
            $value = true;

            $operator = '=';
        }

        if (func_num_args() === 2) {
            $value = $operator;

            $operator = '=';
        }

        return function ($item) use ($key, $operator, $value) {
            $retrieved = enum_value(data_get($item, $key));
            $value = enum_value($value);

            $strings = array_filter([$retrieved, $value], function ($value) {
                return match (true) {
                    is_string($value) => true,
                    $value instanceof \Stringable => true,
                    default => false,
                };
            });

            if (count($strings) < 2 && count(array_filter([$retrieved, $value], 'is_object')) == 1) {
                return in_array($operator, ['!=', '<>', '!==']);
            }

            switch ($operator) {
                default:
                case '=':
                case '==':  return $retrieved == $value;
                case '!=':
                case '<>':  return $retrieved != $value;
                case '<':   return $retrieved < $value;
                case '>':   return $retrieved > $value;
                case '<=':  return $retrieved <= $value;
                case '>=':  return $retrieved >= $value;
                case '===': return $retrieved === $value;
                case '!==': return $retrieved !== $value;
                case '<=>': return $retrieved <=> $value;
            }
        };
    }

    /**
     * Determine if the given value is callable, but not a string.
     *
     * @param  mixed  $value
     * @return bool
     */
    protected function useAsCallable(mixed $value): bool
    {
        return ! is_string($value) && is_callable($value);
    }

    /**
     * Get a value retrieving callback.
     *
     * @param  callable|string|null  $value
     * @return callable
     */
    protected function valueRetriever($value): callable
    {
        if ($this->useAsCallable($value)) {
            return $value;
        }

        return fn ($item) => data_get($item, $value);
    }

    /**
     * Make a function to check an item's equality.
     *
     * @param  mixed  $value
     * @return \Closure(mixed): bool
     */
    protected function equality(mixed $value): Closure
    {
        return fn ($item) => $item === $value;
    }

    /**
     * Make a function using another function, by negating its result.
     *
     * @param  \Closure  $callback
     * @return \Closure
     */
    protected function negate(Closure $callback): Closure
    {
        return fn (...$params) => ! $callback(...$params);
    }

    /**
     * Make a function that returns what's passed to it.
     *
     * @return \Closure(TValue): TValue
     */
    protected function identity(): Closure
    {
        return fn ($value) => $value;
    }
}
