<?php

namespace Illuminate\Database\Eloquent\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\ComparesCastableAttributes;

/**
 * An internal cast built from closures.
 *
 * Used by the model to represent each built-in cast type as a reusable cast
 * object so the dispatch decision can be resolved once per class + cast type
 * and memoized, instead of being re-evaluated on every attribute access.
 *
 * The closures always defer back to the model's own cast methods so userland
 * overrides of those methods are preserved.
 *
 * @template TValue
 * @template TRawValue
 *
 * @internal
 */
class ClosureCast implements CastsAttributes, ComparesCastableAttributes
{
    /**
     * The closure used to transform the attribute from its stored value.
     *
     * @var callable(\Illuminate\Database\Eloquent\Model, string, TRawValue, array<string, mixed>): TValue
     */
    protected $get;

    /**
     * The closure used to transform the attribute to its stored value.
     *
     * @var callable(\Illuminate\Database\Eloquent\Model, string, TValue, array<string, mixed>): TRawValue
     */
    protected $set;

    /**
     * The closure used to compare two values for the attribute.
     *
     * @var callable(\Illuminate\Database\Eloquent\Model, string, TValue, TValue): bool
     */
    protected $comparator;

    /**
     * Create a new closure cast instance.
     *
     * @param  callable(\Illuminate\Database\Eloquent\Model, string, TRawValue, array<string, mixed>): TValue  $get
     * @param  (callable(\Illuminate\Database\Eloquent\Model, string, TValue, array<string, mixed>): TRawValue)|null  $set
     * @param  (callable(\Illuminate\Database\Eloquent\Model, string, TValue, TValue): bool)|null  $comparator
     * @param  bool  $setsOwnAttribute  Whether the set closure writes the model's attributes itself.
     * @param  bool  $nullable  Whether a null stored value should resolve to null without calling the get closure.
     */
    public function __construct(
        callable $get,
        ?callable $set = null,
        ?callable $comparator = null,
        public bool $setsOwnAttribute = false,
        protected bool $nullable = true,
    ) {
        $this->get = $get;
        $this->set = $set ?: fn ($model, $key, $value) => $value;
        $this->comparator = $comparator ?: fn () => false;
    }

    /**
     * Transform the attribute from the underlying model values.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array<string, mixed>  $attributes
     * @return mixed
     */
    public function get($model, string $key, mixed $value, array $attributes)
    {
        if (is_null($value) && $this->nullable) {
            return null;
        }

        return ($this->get)($model, $key, $value, $attributes);
    }

    /**
     * Transform the attribute to its underlying model values.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array<string, mixed>  $attributes
     * @return mixed
     */
    public function set($model, string $key, mixed $value, array $attributes)
    {
        return ($this->set)($model, $key, $value, $attributes);
    }

    /**
     * Determine if the given values are equal.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $firstValue
     * @param  mixed  $secondValue
     * @return bool
     */
    public function compare($model, string $key, mixed $firstValue, mixed $secondValue)
    {
        return (bool) ($this->comparator)($model, $key, $firstValue, $secondValue);
    }
}
