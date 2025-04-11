<?php

namespace Illuminate\Database\Eloquent\Concerns;

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
     * Save the model to the database if the condition is true.
     *
     * @param  (\Closure($this):mixed)|mixed  $condition
     * @param  array  $options
     * @param  bool  $quietly
     * @return bool
     */
    public function saveWhen($condition, array $options = [], $quietly = false)
    {
        if (value($condition, $this)) {
            return $quietly ? $this->saveQuietly($options) : $this->save($options);
        }

        return false;
    }

    /**
     * Save the model to the database if the condition is true.
     *
     * @param  (\Closure($this):mixed)|mixed  $condition
     * @param  array  $options
     * @param  bool  $quietly
     * @return bool
     */
    public function saveUnless($condition, array $options = [], $quietly = false)
    {
        if (! value($condition, $this)) {
            return $quietly ? $this->saveQuietly($options) : $this->save($options);
        }

        return false;
    }

    /**
     * Save the model to the database if the condition is true.
     *
     * @param  (\Closure($this):mixed)|mixed  $condition
     * @param  array  $options
     * @return bool
     */
    public function saveQuietlyWhen($condition, array $options = [])
    {
        return $this->saveWhen($condition, $options, true);
    }

    /**
     * Save the model to the database if the condition is true.
     *
     * @param  (\Closure($this):mixed)|mixed  $condition
     * @param  array  $options
     * @return bool
     */
    public function saveQuietlyUnless($condition, array $options = [])
    {
        return $this->saveUnless($condition, $options, true);
    }

    /**
     * Save the model to the database if the condition is true.
     *
     * @param  (\Closure($this):mixed)|mixed  $condition
     * @param  (\Closure($this):array)|array  $attributes
     * @param  array  $options
     * @param  bool  $quietly
     * @return bool
     */
    public function updateWhen($condition, $attributes = [], array $options = [], $quietly = false)
    {
        if (value($condition, $this)) {
            return $quietly
                ? $this->updateQuietly(value($attributes, $this), $options)
                : $this->update(value($attributes, $this), $options);
        }

        return false;
    }

    /**
     * Save the model to the database if the condition is true.
     *
     * @param  (\Closure($this):mixed)|mixed  $condition
     * @param  (\Closure($this):array)|array  $attributes
     * @param  array  $options
     * @param  bool  $quietly
     * @return bool
     */
    public function updateUnless($condition, $attributes = [], array $options = [], $quietly = false)
    {
        if (! value($condition, $this)) {
            return $quietly
                ? $this->updateQuietly(value($attributes, $this), $options)
                : $this->update(value($attributes, $this), $options);
        }

        return false;
    }

    /**
     * Save the model to the database if the condition is true, without raising any events.
     *
     * @param  (\Closure($this):mixed)|mixed  $condition
     * @param  (\Closure($this):array)|array  $attributes
     * @param  array  $options
     * @return bool
     */
    public function updateQuietlyWhen($condition, $attributes = [], array $options = [])
    {
        return $this->updateWhen($condition, $attributes, $options, true);
    }

    /**
     * Save the model to the database if the condition is true, without raising any events.
     *
     * @param  (\Closure($this):mixed)|mixed  $condition
     * @param  (\Closure($this):array)|array  $attributes
     * @param  array  $options
     * @return bool
     */
    public function updateQuietlyUnless($condition, $attributes = [], array $options = [])
    {
        return $this->updateUnless($condition, $attributes, $options, true);
    }

    /**
     * Delete the model to the database if the condition is truthy.
     *
     * @param  (\Closure($this):mixed)|mixed  $condition
     * @param  bool  $quietly
     * @return bool
     */
    public function deleteWhen($condition, $quietly = false)
    {
        if (value($condition, $this)) {
            return $quietly ? $this->deleteQuietly() : $this->delete();
        }

        return false;
    }

    /**
     * Delete the model to the database if the condition is falsy.
     *
     * @param  (\Closure($this):mixed)|mixed  $condition
     * @param  bool  $quietly
     * @return bool
     */
    public function deleteUnless($condition, $quietly = false)
    {
        if (! value($condition, $this)) {
            return $quietly ? $this->deleteQuietly() : $this->delete();
        }

        return false;
    }

    /**
     * Delete the model to the database if the condition is truthy, without raising any events.
     *
     * @param  (\Closure($this):mixed)|mixed  $condition
     * @return bool
     */
    public function deleteQuietlyWhen($condition)
    {
        return $this->deleteWhen($condition, true);
    }

    /**
     * Delete the model to the database if the condition is falsy, without raising any events.
     *
     * @param  (\Closure($this):mixed)|mixed  $condition
     * @return bool
     */
    public function deleteQuietlyUnless($condition)
    {
        return $this->deleteUnless($condition, true);
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
        return ! value($condition, $this) ? $this->forceDelete() : false;
    }
}
