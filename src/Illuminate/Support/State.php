<?php

namespace Illuminate\Support;

use BadMethodCallException;

class State
{
    /**
     * Current state.
     *
     * @var string
     */
    protected $current;

    /**
     * Possible states for this current instance.
     *
     * @var array
     */
    protected $states = [];

    /**
     * Create a new instance with a list of available states.
     *
     * @param  array|null  $states
     */
    public function __construct(array $states = null)
    {
        $this->states = $states ?? $this->states;
    }

    /**
     * Return the enumerated value.
     *
     * @return mixed
     */
    public function value()
    {
        return array_key_exists($this->current, $this->states)
            ? $this->states[$this->current]
            : $this->current;
    }

    /**
     * Returns if the state exists.
     *
     * @param  string  $state
     * @return bool
     */
    public function has(string $state)
    {
        if (is_string(array_key_first($this->states))) {
            return array_key_exists($state, $this->states);
        }

        return in_array($state, $this->states, true);
    }

    /**
     * Returns if the current state is equal to the issued one.
     *
     * @param  string  $state
     * @return bool
     */
    public function is(string $state)
    {
        return $this->current === $state;
    }

    /**
     * Return the current state.
     *
     * @return string|null
     */
    public function current()
    {
        return $this->current;
    }

    /**
     * Return all possible states.
     *
     * @return array
     */
    public function states()
    {
        return $this->states;
    }

    /**
     * Handle dynamically setting the state.
     *
     * @param  string  $name
     * @param  array  $arguments
     * @return \Illuminate\Support\State
     *
     * @throws \BadMethodCallException
     */
    public function __call($name, $arguments)
    {
        if ($this->has($name)) {
            $this->current = $name;

            return $this;
        }

        throw new BadMethodCallException("The state [$name] is not in the list of possible states.");
    }

    /**
     * Transform the class instance into a string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->current ?? '';
    }

    /**
     * Creates a new Enumerate instance.
     *
     * @param  array  $states
     * @param  string|null  $initial
     * @return mixed
     */
    public static function from(array $states, string $initial = null)
    {
        $instance = (new static($states));

        if ($initial) {
            $instance->{$initial}();
        }

        return $instance;
    }
}
