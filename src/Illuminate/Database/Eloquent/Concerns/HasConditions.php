<?php

namespace Illuminate\Database\Eloquent\Concerns;

use function value;

trait HasConditions
{
    /**
     * Fill the model with an array of attributes if the condition is truthy.
     *
     * @param  (\Closure($this):mixed)|mixed  $condition
     * @param  (\Closure($this):array)|array  $attributes
     * @return $this
     */
    public function fillWhen($condition, $attributes = [])
    {
        return value($condition, $this) ? $this->fill(value($attributes, $this)) : $this;
    }

    /**
     * Fill the model with an array of attributes if the condition is falsy.
     *
     * @param  (\Closure($this):mixed)|mixed  $condition
     * @param  (\Closure($this):array)|array  $attributes
     * @return $this
     */
    public function fillUnless($condition, $attributes = [])
    {
        return ! value($condition, $this) ? $this->fill(value($attributes, $this)) : $this;
    }

    /**
     * Fill the model with an array of attributes if the condition is truthy. Force mass assignment.
     *
     * @param  (\Closure($this):mixed)|mixed  $condition
     * @param  (\Closure($this):array)|array  $attributes
     * @return $this
     */
    public function forceFillWhen($condition, $attributes = [])
    {
        return value($condition, $this) ? $this->forceFill(value($attributes, $this)) : $this;
    }

    /**
     * Fill the model with an array of attributes if the condition is falsy. Force mass assignment.
     *
     * @param  (\Closure($this):mixed)|mixed  $condition
     * @param  (\Closure($this):array)|array  $attributes
     * @return $this
     */
    public function forceFillUnless($condition, $attributes = [])
    {
        return ! value($condition, $this) ? $this->forceFill(value($attributes, $this)) : $this;
    }

    /**
     * Save the model to the database if the condition is true
     *
     * @param  (\Closure($this):mixed)|mixed  $condition
     * @param  array  $options
     * @return bool
     */
    public function saveWhen($condition, array $options = [])
    {
        return value($condition, $this) ? $this->save($options) : false;
    }

    /**
     * Save the model to the database if the condition is true
     *
     * @param  (\Closure($this):mixed)|mixed  $condition
     * @param  array  $options
     * @return bool
     */
    public function saveUnless($condition, array $options = [])
    {
        return !value($condition, $this) ? $this->save($options) : false;
    }

    /**
     * Save the model to the database if the condition is true
     *
     * @param  (\Closure($this):mixed)|mixed  $condition
     * @param  (\Closure($this):array)|array  $attributes
     * @param  array  $options
     * @return bool
     */
    public function updateWhen($condition, $attributes = [], array $options = [])
    {
        return value($condition, $this) ? $this->update(value($attributes, $this), $options) : false;
    }

    /**
     * Save the model to the database if the condition is true
     *
     * @param  (\Closure($this):mixed)|mixed  $condition
     * @param  (\Closure($this):array)|array  $attributes
     * @param  array  $options
     * @return bool
     */
    public function updateUnless($condition, $attributes = [], array $options = [])
    {
        return !value($condition, $this) ? $this->update(value($attributes, $this), $options) : false;
    }

    /**
     * Delete the model to the database if the condition is truthy.
     *
     * @param  (\Closure($this):mixed)|mixed  $condition
     * @return bool
     */
    public function deleteWhen($condition)
    {
        return value($condition, $this) ? $this->delete() : false;
    }

    /**
     * Delete the model to the database if the condition is falsy.
     *
     * @param  (\Closure($this):mixed)|mixed  $condition
     * @return bool
     */
    public function deleteUnless($condition)
    {
        return !value($condition, $this) ? $this->delete() : false;
    }

    /**
     * Force delete the model from the database if the condition is truthy.
     *
     * @param  (\Closure($this):mixed)|mixed  $condition
     * @return bool
     */
    public function forceDeleteWhen($condition)
    {
        return value($condition, $this) ? $this->forceDelete() : false;
    }

    /**
     * Force delete the model from the database if the condition is falsy.
     *
     * @param  (\Closure($this):mixed)|mixed  $condition
     * @return bool
     */
    public function forceDeleteUnless($condition)
    {
        return !value($condition, $this) ? $this->forceDelete() : false;
    }
}
