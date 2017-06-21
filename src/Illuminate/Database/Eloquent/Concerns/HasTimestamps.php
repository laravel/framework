<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Carbon\Carbon;

trait HasTimestamps
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Update the model's update timestamp.
     *
     * @param  string|null  $column
     * @return bool
     */
    public function touch($column = null)
    {
        if (! $this->usesTimestamps()) {
            return false;
        }

        $this->updateTimestamps($column);

        return $this->save();
    }

    /**
     * Update the creation and update timestamps.
     *
     * @param  string|null  $column
     * @return void
     */
    protected function updateTimestamps($column = null)
    {
        $time = $this->freshTimestamp();

        if ($column && $this->isDateAttribute($column)) {
            $this->{$column} = $time;
        }

        if (! $this->isDirty(static::UPDATED_AT)) {
            $this->setUpdatedAt($time);
        }

        if (! $this->exists && ! $this->isDirty(static::CREATED_AT)) {
            $this->setCreatedAt($time);
        }
    }

    /**
     * Set the value of the "created at" attribute.
     *
     * @param  mixed  $value
     * @return $this
     */
    public function setCreatedAt($value)
    {
        $this->{static::CREATED_AT} = $value;

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
        $this->{static::UPDATED_AT} = $value;

        return $this;
    }

    /**
     * Get a fresh timestamp for the model.
     *
     * @return \Carbon\Carbon
     */
    public function freshTimestamp()
    {
        return new Carbon;
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
     * @return string
     */
    public function getCreatedAtColumn()
    {
        return static::CREATED_AT;
    }

    /**
     * Get the name of the "updated at" column.
     *
     * @return string
     */
    public function getUpdatedAtColumn()
    {
        return static::UPDATED_AT;
    }
}
