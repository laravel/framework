<?php

namespace Illuminate\Database\Eloquent\Casters;

abstract class AbstractCaster
{
    /**
     * The caster options.
     *
     * @var array
     */
    protected $options;

    /**
     * Set the caster options.
     *
     * @param array $options
     *
     * @return \Illuminate\Database\Eloquent\Casters\AbstractCaster
     */
    public function options(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Prepare a value to be stored.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    abstract public function as($value);

    /**
     * Prepare a value to be retrieved.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    abstract public function from($value);
}
