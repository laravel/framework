<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\JsonEncodingException;
use Illuminate\Support\Collection;

trait HasCasts
{
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [];

    /**
     * Initialized instances of custom casts.
     *
     * @var \Illuminate\Database\Eloquent\Cast[]
     */
    protected $castsInstances = [];

    /**
     * Determine whether an attribute should be cast to a native type.
     *
     * @param  string  $key
     * @param  array|string|null  $types
     * @return bool
     */
    public function hasCast($key, $types = null)
    {
        if (! array_key_exists($key, $this->getCasts())) {
            return false;
        }

        return $types
            ? in_array($this->getCastType($key), (array) $types, true)
            : true;
    }

    /**
     * Get the casts array.
     *
     * @return array
     */
    public function getCasts()
    {
        return $this->getIncrementing()
            ? array_merge([$this->getKeyName() => $this->getKeyType()], $this->casts)
            : $this->casts;
    }

    /**
     * Get the cast from casts array.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getCast($key)
    {
        return $this->getCasts()[$key] ?? null;
    }

    /**
     * Determine if the new and old values for a given key are equivalent.
     *
     * @param  string  $key
     * @param  mixed  $current
     * @return bool
     */
    public function originalIsEquivalent($key, $current)
    {
        if (! array_key_exists($key, $this->original)) {
            return false;
        }

        $original = $this->getOriginal($key);

        if ($current === $original) {
            return true;
        } elseif (is_null($current)) {
            return false;
        } elseif ($this->isDateAttribute($key)) {
            return $this->fromDateTime($current) ===
                $this->fromDateTime($original);
        } elseif ($this->hasCast($key, ['object', 'collection'])) {
            return $this->castAttribute($key, $current) ==
                $this->castAttribute($key, $original);
        } elseif ($this->hasCast($key)) {
            return $this->castAttribute($key, $current) ===
                $this->castAttribute($key, $original);
        }

        return is_numeric($current) && is_numeric($original)
            && strcmp((string) $current, (string) $original) === 0;
    }

    /**
     * Add the casted attributes to the attributes array.
     *
     * @param  array  $attributes
     * @param  array  $mutatedAttributes
     * @return array
     */
    protected function addCastAttributesToArray(array $attributes, array $mutatedAttributes)
    {
        foreach ($this->getCasts() as $key => $value) {
            if (! array_key_exists($key, $attributes) || in_array($key, $mutatedAttributes)) {
                continue;
            }

            // Here we will cast the attribute. Then, if the cast is a date or datetime cast
            // then we will serialize the date for the array. This will convert the dates
            // to strings based on the date format specified for these Eloquent models.
            $attributes[$key] = $this->castAttribute(
                $key, $attributes[$key]
            );

            // If the attribute cast was a date or a datetime, we will serialize the date as
            // a string. This allows the developers to customize how dates are serialized
            // into an array without affecting how they are persisted into the storage.
            if ($attributes[$key] &&
                ($value === 'date' || $value === 'datetime')) {
                $attributes[$key] = $this->serializeDate($attributes[$key]);
            }

            if ($this->isCustomDateTimeCast($value)) {
                $attributes[$key] = $attributes[$key]->format(explode(':', $value, 2)[1]);
            }

            if ($attributes[$key] instanceof Arrayable) {
                $attributes[$key] = $attributes[$key]->toArray();
            }
        }

        return $attributes;
    }

    /**
     * Cast an attribute to a native PHP type.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function castAttribute($key, $value = null)
    {
        if ($this->isCustomCastable($key)) {
            return $this->fromCustomCastable($key, $value);
        } elseif (is_null($value)) {
            return $value;
        }

        switch ($this->getCastType($key)) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'real':
            case 'float':
            case 'double':
                return $this->fromFloat($value);
            case 'decimal':
                return $this->asDecimal($value, explode(':', $this->getCast($key), 2)[1]);
            case 'string':
                return (string) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'object':
                return $this->fromJson($value, true);
            case 'array':
            case 'json':
                return $this->fromJson($value);
            case 'collection':
                return new Collection($this->fromJson($value));
            case 'date':
                return $this->asDate($value);
            case 'datetime':
            case 'custom_datetime':
                return $this->asDateTime($value);
            case 'timestamp':
                return $this->asTimestamp($value);
            default:
                return $value;
        }
    }

    /**
     * Get the type of cast for a model attribute.
     *
     * @param  string  $key
     * @return string
     */
    protected function getCastType($key)
    {
        $cast = $this->getCast($key);

        if ($this->isCustomDateTimeCast($cast)) {
            return 'custom_datetime';
        }

        if ($this->isDecimalCast($cast)) {
            return 'decimal';
        }

        return trim(strtolower($cast));
    }

    /**
     * Cast the given attribute to JSON.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return string
     */
    protected function castAttributeAsJson($key, $value)
    {
        $value = $this->asJson($value);

        if ($value === false) {
            throw JsonEncodingException::forAttribute(
                $this, $key, json_last_error_msg()
            );
        }

        return $value;
    }

    /**
     * Determine if the cast type is a custom date time cast.
     *
     * @param  string  $cast
     * @return bool
     */
    protected function isCustomDateTimeCast($cast)
    {
        return strncmp($cast, 'date:', 5) === 0 ||
            strncmp($cast, 'datetime:', 9) === 0;
    }

    /**
     * Determine if the cast type is a decimal cast.
     *
     * @param  string  $cast
     * @return bool
     */
    protected function isDecimalCast($cast)
    {
        return strncmp($cast, 'decimal:', 8) === 0;
    }

    /**
     * Determine if the given attribute is a date or date castable.
     *
     * @param  string  $key
     * @return bool
     */
    protected function isDateAttribute($key)
    {
        return in_array($key, $this->getDates(), true) ||
            $this->isDateCastable($key);
    }

    /**
     * Is the checked value a custom Cast.
     *
     * @param  string  $key
     * @return bool
     */
    protected function isCustomCastable($key)
    {
        if ($cast = $this->getCast($key)) {
            return is_subclass_of($cast, Castable::class);
        }

        return false;
    }

    /**
     * Determine whether a value is Date / DateTime castable for inbound manipulation.
     *
     * @param  string  $key
     * @return bool
     */
    protected function isDateCastable($key)
    {
        return $this->hasCast($key, ['date', 'datetime']);
    }

    /**
     * Determine whether a value is JSON castable for inbound manipulation.
     *
     * @param  string  $key
     * @return bool
     */
    protected function isJsonCastable($key)
    {
        return $this->hasCast($key, ['array', 'json', 'object', 'collection']);
    }

    /**
     * Getting the execution result from a user Cast object.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function fromCustomCastable($key, $value = null)
    {
        return $this
            ->normalizeHandlerToCallable($key)
            ->setKeyName($key)
            ->setOriginalValue($value)
            ->get($value);
    }

    /**
     * Converting a value by custom Cast.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function toCustomCastable($key, $value = null)
    {
        return $this
            ->normalizeHandlerToCallable($key)
            ->setKeyName($key)
            ->setOriginalValue($value)
            ->set($value);
    }

    /**
     * Getting a custom cast instance.
     *
     * @param  string  $key
     * @return \Illuminate\Database\Eloquent\Cast
     */
    protected function normalizeHandlerToCallable($key)
    {
        if (! array_key_exists($key, $this->castsInstances)) {
            $cast = $this->getCast($key);

            $this->castsInstances[$key] = new $cast;
        }

        return $this->castsInstances[$key];
    }
}
