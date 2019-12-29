<?php

namespace Illuminate\Database\Eloquent;

use Illuminate\Contracts\Database\Eloquent\Castable;

abstract class Cast implements Castable
{
    /**
     * Name of the variable to be processed.
     *
     * @var string
     */
    protected $keyName;

    /**
     * The original value of the variable.
     *
     * @var mixed
     */
    protected $originalValue;

    /**
     * Getting the field name.
     *
     * @return string
     */
    public function getKeyName()
    {
        return $this->keyName;
    }

    /**
     * Setting the field name.
     *
     * @param  string  $value
     * @return $this
     */
    public function setKeyName($value)
    {
        $this->keyName = $value;

        return $this;
    }

    /**
     * Getting the original value.
     *
     * @return mixed
     */
    public function getOriginalValue()
    {
        return $this->originalValue;
    }

    /**
     * Setting the original value.
     *
     * @param  mixed  $value
     * @return $this
     */
    public function setOriginalValue($value = null)
    {
        $this->originalValue = $value;

        return $this;
    }
}
