<?php

namespace Illuminate\Validation\Rules;

use Closure;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Traits\ForwardsCalls;

/**
 * @template TModel of Model
 *
 * @mixin Builder<TModel>
 */
class ModelExists implements ValidationRule
{
    use ForwardsCalls;

    /**
     * Create a new rule instance.
     */
    public function __construct(
        protected Builder $query,
        protected ?string $column
    ) {
        //
    }

    /**
     * Create a new rule instance with the given Builder, Model, or class name.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>|TModel|class-string<TModel>  $model
     * @return ModelExists<TModel>
     */
    public static function make(Builder|Model|string $model, ?string $column = null): self
    {
        $builder = match (true) {
            $model instanceof Builder => $model,
            $model instanceof Model => $model->query(),
            default => $model::query(),
        };

        return new self($builder, $column);
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $this->column
            ? $this->query->where($this->column, $value)
            : $this->query->whereKey($value);

        if (! $this->query->exists()) {
            $fail('validation.exists')->translate(['attribute' => $attribute]);
        }
    }

    /**
     * Get the underlying query builder instance.
     *
     * @return Builder<TModel>
     */
    public function getQueryBuilder(): Builder
    {
        return $this->query;
    }

    /**
     * Dynamically handle calls into the query instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $this->forwardCallTo($this->query, $method, $parameters);

        return $this;
    }
}
