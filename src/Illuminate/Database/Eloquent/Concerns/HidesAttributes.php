<?php

namespace Illuminate\Database\Eloquent\Concerns;

trait HidesAttributes
{
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [];

    /**
     * The attributes that should be visible in serialization.
     *
     * @var array<string>
     */
    protected $visible = [];

    /**
     * Get the hidden attributes for the model.
     *
     * @return array<string>
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * Set the hidden attributes for the model.
     *
     * @param  array<string>  $hidden
     * @return $this
     */
    public function setHidden(array $hidden)
    {
        $this->hidden = $hidden;

        return $this;
    }

    /**
     * Get the visible attributes for the model.
     *
     * @return array<string>
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * Set the visible attributes for the model.
     *
     * @param  array<string>  $visible
     * @return $this
     */
    public function setVisible(array $visible)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Make the given, typically hidden, attributes visible.
     *
     * @param  array<string>|string  $attributes
     * @return $this
     */
    public function makeVisible($attributes)
    {
        $attributes = is_array($attributes) ? $attributes : func_get_args();

        $this->hidden = array_diff($this->hidden, $attributes);

        if (! empty($this->visible)) {
            $this->visible = array_merge($this->visible, $attributes);
        }

        return $this;
    }

    /**
     * Make the given, typically hidden, attributes visible if the given truth test passes.
     *
     * @param  bool|\Closure  $condition
     * @param  array<string>|string  $attributes
     * @return $this
     */
    public function makeVisibleIf($condition, $attributes)
    {
        return value($condition, $this) ? $this->makeVisible($attributes) : $this;
    }

    /**
     * Indicates if the given attribute is visible.
     *
     * @param  string  $attribute
     * @return bool
     */
    public function isVisible($attribute)
    {
        return ! $this->isHidden($attribute);
    }

    /**
     * Make the given, typically visible, attributes hidden.
     *
     * @param  array<string>|string  $attributes
     * @return $this
     */
    public function makeHidden($attributes)
    {
        $this->hidden = array_merge(
            $this->hidden, is_array($attributes) ? $attributes : func_get_args()
        );

        return $this;
    }

    /**
     * Make the given, typically visible, attributes hidden if the given truth test passes.
     *
     * @param  bool|\Closure  $condition
     * @param  array<string>|string  $attributes
     * @return $this
     */
    public function makeHiddenIf($condition, $attributes)
    {
        return value($condition, $this) ? $this->makeHidden($attributes) : $this;
    }

    /**
     * Indicates if the given attribute is hidden.
     *
     * @param  string  $attribute
     * @return bool
     */
    public function isHidden($attribute)
    {
        return in_array($attribute, $this->hidden);
    }
}
