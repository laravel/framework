<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Closure;

trait HidesAttributes
{
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * The attributes that should be visible in serialization.
     *
     * @var array
     */
    protected $visible = [];

    /**
     * Get the hidden attributes for the model.
     *
     * @return array
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * Set the hidden attributes for the model.
     *
     * @param  array  $hidden
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
     * @return array
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * Set the visible attributes for the model.
     *
     * @param  array  $visible
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
     * @param  array|string|null  $attributes
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
     * Make the given, typically visible, attributes hidden.
     *
     * @param  array|string|null  $attributes
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
     * Make the given, typically hidden, attributes visible,
     * only if the truth test passes.
     *
     * @param  bool|Closure  $truthTest
     * @param  array|string|null  $attributes
     * @return $this
     */
    public function makeVisibleIf($truthTest, $attributes)
    {
        if ($truthTest instanceof Closure) {
            $truthTest = $truthTest($this);
        }

        if ($truthTest) {
            $this->makeVisible($attributes);
        }

        return $this;
    }

    /**
     * Make the given, typically visible, attributes hidden,
     * only if the truth test passes.
     *
     * @param  bool|Closure  $truthTest
     * @param  array|string|null  $attributes
     * @return $this
     */
    public function makeHiddenIf($truthTest, $attributes)
    {
        if ($truthTest instanceof Closure) {
            $truthTest = $truthTest($this);
        }

        if ($truthTest) {
            $this->makeHidden($attributes);
        }

        return $this;
    }
}
