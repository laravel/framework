<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Illuminate\Support\Facades\Date;

trait HasTimestamps
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The list of models classes that should not have timestamps updated on update.
     *
     * @var array
     */
    protected static $ignoreUpdatedTimestamp = [];

    /**
     * Update the model's update timestamp.
     *
     * @param  string|null  $attribute
     * @return bool
     */
    public function touch($attribute = null)
    {
        if ($attribute) {
            $this->$attribute = $this->freshTimestamp();

            return $this->save();
        }

        if (! $this->usesTimestamps()) {
            return false;
        }

        $this->updateTimestamps();

        return $this->save();
    }

    /**
     * Update the creation and update timestamps.
     *
     * @return $this
     */
    public function updateTimestamps()
    {
        $time = $this->freshTimestamp();

        $updatedAtColumn = $this->getUpdatedAtColumn();

        if (! is_null($updatedAtColumn) && ! $this->isDirty($updatedAtColumn) && (! $this->exists || ! static::isIgnoringUpdatedTimestamp())) {
            $this->setUpdatedAt($time);
        }

        $createdAtColumn = $this->getCreatedAtColumn();

        if (! $this->exists && ! is_null($createdAtColumn) && ! $this->isDirty($createdAtColumn)) {
            $this->setCreatedAt($time);
        }

        return $this;
    }

    /**
     * Set the value of the "created at" attribute.
     *
     * @param  mixed  $value
     * @return $this
     */
    public function setCreatedAt($value)
    {
        $this->{$this->getCreatedAtColumn()} = $value;

        return $this;
    }

    /**
     * Set the value of the "updated at" attribute.
     *
     * @param  mixed  $value
     * @return $this
     */
    public function setUpdatedAt($value)
    {
        $this->{$this->getUpdatedAtColumn()} = $value;

        return $this;
    }

    /**
     * Get a fresh timestamp for the model.
     *
     * @return \Illuminate\Support\Carbon
     */
    public function freshTimestamp()
    {
        return Date::now();
    }

    /**
     * Get a fresh timestamp for the model.
     *
     * @return string
     */
    public function freshTimestampString()
    {
        return $this->fromDateTime($this->freshTimestamp());
    }

    /**
     * Determine if the model uses timestamps.
     *
     * @return bool
     */
    public function usesTimestamps()
    {
        return $this->timestamps;
    }

    /**
     * Get the name of the "created at" column.
     *
     * @return string|null
     */
    public function getCreatedAtColumn()
    {
        return static::CREATED_AT;
    }

    /**
     * Get the name of the "updated at" column.
     *
     * @return string|null
     */
    public function getUpdatedAtColumn()
    {
        return static::UPDATED_AT;
    }

    /**
     * Get the fully qualified "created at" column.
     *
     * @return string|null
     */
    public function getQualifiedCreatedAtColumn()
    {
        return $this->qualifyColumn($this->getCreatedAtColumn());
    }

    /**
     * Get the fully qualified "updated at" column.
     *
     * @return string|null
     */
    public function getQualifiedUpdatedAtColumn()
    {
        return $this->qualifyColumn($this->getUpdatedAtColumn());
    }

    /**
     * Disables setting the updated timestamp column for the current class during given callback scope.
     *
     * @param  callable  $callback
     * @return void
     */
    public static function withoutUpdatedTimestamp(callable $callback)
    {
        static::withoutUpdatedTimestampOn([static::class], $callback);
    }

    /**
     * Disables setting the updated timestamp column for the given model classes during given callback scope.
     *
     * @param  array  $models
     * @param  callable  $callback
     * @return void
     */
    public static function withoutUpdatedTimestampOn(array $models, $callback)
    {
        static::$ignoreUpdatedTimestamp = array_values(array_merge(static::$ignoreUpdatedTimestamp, $models));

        try {
            $callback();
        } finally {
            static::$ignoreUpdatedTimestamp = array_values(array_diff(static::$ignoreUpdatedTimestamp, $models));
        }
    }

    /**
     * Determine if the model is ignoring the updated timestamp.
     *
     * @return bool
     */
    public static function isIgnoringUpdatedTimestamp($class = null)
    {
        $class = $class ?: static::class;

        if (! get_class_vars($class)['timestamps'] || ! $class::UPDATED_AT) {
            return true;
        }

        foreach (static::$ignoreUpdatedTimestamp as $ignoredClass) {
            if ($class === $ignoredClass || is_subclass_of($class, $ignoredClass)) {
                return true;
            }
        }

        return false;
    }
}
