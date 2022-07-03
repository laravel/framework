<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Illuminate\Support\Facades\Auth;

trait HasUserstamps
{
    /**
     * Indicates if the model should be userstamped.
     *
     * @var bool
     */
    public $userstamps = true;

    /**
     * Update the creation and update userstamps.
     *
     * @return $this
     */
    public function updateUserstamps()
    {
        $user = $this->freshUserstamp();

        $updatedByColumn = $this->getUpdatedByColumn();

        if (! is_null($updatedByColumn) && ! $this->isDirty($updatedByColumn)) {
            $this->setUpdatedBy($user);
        }

        $createdByColumn = $this->getCreatedByColumn();

        if (! $this->exists && ! is_null($createdByColumn) && ! $this->isDirty($createdByColumn)) {
            $this->setCreatedBy($user);
        }

        return $this;
    }

    /**
     * Set the value of the "created at" attribute.
     *
     * @param  mixed  $value
     * @return $this
     */
    public function setCreatedBy($value)
    {
        $this->{$this->getCreatedByColumn()} = $value;

        return $this;
    }

    /**
     * Set the value of the "updated at" attribute.
     *
     * @param  mixed  $value
     * @return $this
     */
    public function setUpdatedBy($value)
    {
        $this->{$this->getUpdatedByColumn()} = $value;

        return $this;
    }

    /**
     * Get a fresh userstamp for the model.
     *
     * @return string|null
     */
    public function freshUserstamp()
    {
        return Auth::check() ? Auth::id() : null;
    }

    /**
     * Determine if the model uses timestamps.
     *
     * @return bool
     */
    public function usesUserstamps()
    {
        return $this->userstamps;
    }

    /**
     * Get the name of the "created at" column.
     *
     * @return string|null
     */
    public function getCreatedByColumn()
    {
        return static::CREATED_BY;
    }

    /**
     * Get the name of the "updated at" column.
     *
     * @return string|null
     */
    public function getUpdatedByColumn()
    {
        return static::UPDATED_BY;
    }

    /**
     * Get the fully qualified "created at" column.
     *
     * @return string|null
     */
    public function getQualifiedCreatedByColumn()
    {
        return $this->qualifyColumn($this->getCreatedByColumn());
    }

    /**
     * Get the fully qualified "updated at" column.
     *
     * @return string|null
     */
    public function getQualifiedUpdatedByColumn()
    {
        return $this->qualifyColumn($this->getUpdatedByColumn());
    }
}
