<?php

namespace Illuminate\Http\Resources;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait ConditionallyLoadsAttributes
{
    /**
     * Filter the given data, removing any optional values.
     *
     * @param  array  $data
     * @return array
     */
    protected function filter($data)
    {
        $index = -1;

        foreach ($data as $key => $value) {
            $index++;

            if (is_array($value)) {
                $data[$key] = $this->filter($value);

                continue;
            }

            if (is_numeric($key) && $value instanceof MergeValue) {
                return $this->mergeData(
                    $data, $index, $this->filter($value->data),
                    array_values($value->data) === $value->data
                );
            }

            if ($value instanceof self && is_null($value->resource)) {
                $data[$key] = null;
            }
        }

        return $this->removeMissingValues($data);
    }

    /**
     * Merge the given data in at the given index.
     *
     * @param  array  $data
     * @param  int  $index
     * @param  array  $merge
     * @param  bool  $numericKeys
     * @return array
     */
    protected function mergeData($data, $index, $merge, $numericKeys)
    {
        if ($numericKeys) {
            return $this->removeMissingValues(array_merge(
                array_merge(array_slice($data, 0, $index, true), $merge),
                $this->filter(array_values(array_slice($data, $index + 1, null, true)))
            ));
        }

        return $this->removeMissingValues(array_slice($data, 0, $index, true) +
                $merge +
                $this->filter(array_slice($data, $index + 1, null, true)));
    }

    /**
     * Remove the missing values from the filtered data.
     *
     * @param  array  $data
     * @return array
     */
    protected function removeMissingValues($data)
    {
        $numericKeys = true;

        foreach ($data as $key => $value) {
            if (($value instanceof PotentiallyMissing && $value->isMissing()) ||
                ($value instanceof self &&
                $value->resource instanceof PotentiallyMissing &&
                $value->isMissing())) {
                unset($data[$key]);
            } else {
                $numericKeys = $numericKeys && is_numeric($key);
            }
        }

        if (property_exists($this, 'preserveKeys') && $this->preserveKeys === true) {
            return $data;
        }

        return $numericKeys ? array_values($data) : $data;
    }

    /**
     * Retrieve a value based on a given condition.
     *
     * @param  bool  $condition
     * @param  mixed  $value
     * @param  mixed  $default
     * @return \Illuminate\Http\Resources\MissingValue|mixed
     */
    protected function when($condition, $value, $default = null)
    {
        if ($condition) {
            return value($value);
        }

        return func_num_args() === 3 ? value($default) : new MissingValue;
    }

    /**
     * Merge a value into the array.
     *
     * @param  mixed  $value
     * @return \Illuminate\Http\Resources\MergeValue|mixed
     */
    protected function merge($value)
    {
        return $this->mergeWhen(true, $value);
    }

    /**
     * Merge a value if the given condition is truthy.
     *
     * @param  bool  $condition
     * @param  mixed  $value
     * @return \Illuminate\Http\Resources\MergeValue|mixed
     */
    protected function mergeWhen($condition, $value)
    {
        return $condition ? new MergeValue(value($value)) : new MissingValue;
    }

    /**
     * Merge a value unless the given condition is truthy.
     *
     * @param  bool  $condition
     * @param  mixed  $value
     * @return \Illuminate\Http\Resources\MergeValue|mixed
     */
    protected function mergeUnless($condition, $value)
    {
        return ! $condition ? new MergeValue(value($value)) : new MissingValue;
    }

    /**
     * Merge the given attributes.
     *
     * @param  array  $attributes
     * @return \Illuminate\Http\Resources\MergeValue
     */
    protected function attributes($attributes)
    {
        return new MergeValue(
            Arr::only($this->resource->toArray(), $attributes)
        );
    }

    /**
     * Retrieve a model attribute if it is null.
     *
     * @param  mixed  $value
     * @param  mixed  $default
     * @return \Illuminate\Http\Resources\MissingValue|mixed
     */
    protected function whenNull($value, $default = null)
    {
        $arguments = func_num_args() == 1 ? [$value] : [$value, $default];

        return $this->when(is_null($value), ...$arguments);
    }

    /**
     * Retrieve a model attribute if it is not null.
     *
     * @param  mixed  $value
     * @param  mixed  $default
     * @return \Illuminate\Http\Resources\MissingValue|mixed
     */
    protected function whenNotNull($value, $default = null)
    {
        $arguments = func_num_args() == 1 ? [$value] : [$value, $default];

        return $this->when(! is_null($value), ...$arguments);
    }

    /**
     * Retrieve an accessor when it has been appended.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  mixed  $default
     * @return \Illuminate\Http\Resources\MissingValue|mixed
     */
    protected function whenAppended($attribute, $value = null, $default = null)
    {
        if ($this->resource->hasAppended($attribute)) {
            return func_num_args() >= 2 ? value($value) : $this->resource->$attribute;
        }

        return func_num_args() === 3 ? value($default) : new MissingValue;
    }

    /**
     * Retrieve a relationship if it has been loaded.
     *
     * @param  string  $relationship
     * @param  mixed  $value
     * @param  mixed  $default
     * @return \Illuminate\Http\Resources\MissingValue|mixed
     */
    protected function whenLoaded($relationship, $value = null, $default = null)
    {
        if (func_num_args() < 3) {
            $default = new MissingValue;
        }

        if (! $this->resource->relationLoaded($relationship)) {
            return value($default);
        }

        if (func_num_args() === 1) {
            return $this->resource->{$relationship};
        }

        if ($this->resource->{$relationship} === null) {
            return;
        }

        return value($value);
    }

    /**
     * Retrieve a relationship count if it exists.
     *
     * @param  string  $relationship
     * @param  mixed  $value
     * @param  mixed  $default
     * @return \Illuminate\Http\Resources\MissingValue|mixed
     */
    public function whenCounted($relationship, $value = null, $default = null)
    {
        if (func_num_args() < 3) {
            $default = new MissingValue();
        }

        $attribute = (string) Str::of($relationship)->snake()->finish('_count');

        if (! isset($this->resource->getAttributes()[$attribute])) {
            return value($default);
        }

        if (func_num_args() === 1) {
            return $this->resource->{$attribute};
        }

        if ($this->resource->{$attribute} === null) {
            return;
        }

        return value($value, $this->resource->{$attribute});
    }

    /**
     * Execute a callback if the given pivot table has been loaded.
     *
     * @param  string  $table
     * @param  mixed  $value
     * @param  mixed  $default
     * @return \Illuminate\Http\Resources\MissingValue|mixed
     */
    protected function whenPivotLoaded($table, $value, $default = null)
    {
        return $this->whenPivotLoadedAs('pivot', ...func_get_args());
    }

    /**
     * Execute a callback if the given pivot table with a custom accessor has been loaded.
     *
     * @param  string  $accessor
     * @param  string  $table
     * @param  mixed  $value
     * @param  mixed  $default
     * @return \Illuminate\Http\Resources\MissingValue|mixed
     */
    protected function whenPivotLoadedAs($accessor, $table, $value, $default = null)
    {
        if (func_num_args() === 3) {
            $default = new MissingValue;
        }

        return $this->when(
            isset($this->resource->$accessor) &&
            ($this->resource->$accessor instanceof $table ||
            $this->resource->$accessor->getTable() === $table),
            ...[$value, $default]
        );
    }

    /**
     * Transform the given value if it is present.
     *
     * @param  mixed  $value
     * @param  callable  $callback
     * @param  mixed  $default
     * @return mixed
     */
    protected function transform($value, callable $callback, $default = null)
    {
        return transform(
            $value, $callback, func_num_args() === 3 ? $default : new MissingValue
        );
    }
}
