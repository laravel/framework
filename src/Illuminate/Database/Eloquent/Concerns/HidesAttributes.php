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
     * The attributes that should be globally hidden for serialization on all models.
     *
     * @var array<string>
     */
    protected static array $globalHidden = [];

    /**
     * The attributes that should be visible in serialization.
     *
     * @var array<string>
     */
    protected $visible = [];

    /**
     * The attributes that should be globally visible for serialization on all models.
     *
     * @var array<string>
     */
    protected static array $globalVisible = [];

    /**
     * Get the hidden attributes for the model.
     *
     * @return array<string>
     */
    public function getHidden()
    {
        $this->mergeHidden(static::$globalHidden);

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
     * Merge new hidden attributes with existing hidden attributes on the model.
     *
     * @param  array<string>  $hidden
     * @return $this
     */
    public function mergeHidden(array $hidden)
    {
        $this->hidden = array_values(array_unique(array_merge($this->hidden, $hidden)));

        return $this;
    }

    /**
     * Get the visible attributes for the model.
     *
     * @return array<string>
     */
    public function getVisible()
    {
        $this->mergeVisible(static::$globalVisible);

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
     * Merge new visible attributes with existing visible attributes on the model.
     *
     * @param  array<string>  $visible
     * @return $this
     */
    public function mergeVisible(array $visible)
    {
        $this->visible = array_values(array_unique(array_merge($this->visible, $visible)));

        return $this;
    }

    /**
     * Make the given, typically hidden, attributes visible.
     *
     * @param  array<string>|string|null  $attributes
     * @return $this
     */
    public function makeVisible($attributes)
    {
        $attributes = is_array($attributes) ? $attributes : func_get_args();

        $this->hidden = array_diff($this->hidden, $attributes);

        if (! empty($this->visible)) {
            $this->visible = array_values(array_unique(array_merge($this->visible, $attributes)));
        }

        return $this;
    }

    /**
     * Make the given, typically hidden, attributes visible if the given truth test passes.
     *
     * @param  bool|\Closure  $condition
     * @param  array<string>|string|null  $attributes
     * @return $this
     */
    public function makeVisibleIf($condition, $attributes)
    {
        return value($condition, $this) ? $this->makeVisible($attributes) : $this;
    }

    /**
     * Make the given, typically visible, attributes hidden.
     *
     * @param  array<string>|string|null  $attributes
     * @return $this
     */
    public function makeHidden($attributes)
    {
        $this->hidden = array_values(array_unique(array_merge(
            $this->hidden, is_array($attributes) ? $attributes : func_get_args()
        )));

        return $this;
    }

    /**
     * Make the given, typically visible, attributes hidden if the given truth test passes.
     *
     * @param  bool|\Closure  $condition
     * @param  array<string>|string|null  $attributes
     * @return $this
     */
    public function makeHiddenIf($condition, $attributes)
    {
        return value($condition, $this) ? $this->makeHidden($attributes) : $this;
    }

    /**
     * Set the globally hidden attributes for all models.
     *
     * @param  array<string> $hidden
     * @return void
     */
    public static function setGloballyHidden(array $hidden): void
    {
        static::$globalHidden = $hidden;
    }

    /**
     * Set the globally visible attributes for all models.
     *
     * @param  array<string> $visible
     * @return void
     */
    public static function setGloballyVisible(array $visible): void
    {
        static::$globalVisible = $visible;
    }
}
