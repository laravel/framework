<?php

namespace Illuminate\Database;

use JsonSerializable;

class BlobValue implements JsonSerializable
{
    private $_value = '';

    public function __construct($value)
    {
        $this->_value = strval($value);
    }

    /**
     *
     * @return string
     */
    public function __toString()
    {
        return $this->_value;
    }

    /**
     *
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->_value;
    }
}
