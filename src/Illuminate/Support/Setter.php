<?php

namespace Illuminate\Support;

use Error;
use TypeError;

class Setter
{

    /**
     * @var array
     */
    protected $fields = [];

    /**
     * @param array $allowedKeys
     */
    public function __construct($allowedKeys)
    {
        $this->fields = array_fill_keys($allowedKeys, null);
    }

    /**
     * Retrieve all values after setting them.
     *
     * @return array
     */
    public function retrieve()
    {
        return $this->fields;
    }

    /**
     * Handle setting of any of allowed fields.
     *
     * @param  string $field
     * @param  array  $arguments
     *
     * @return $this
     * @throws Error when the field does not exist
     * @throws TypeError when no arguments were passed
     */
    public function __call($field, $arguments)
    {
        if (! array_key_exists($field, $this->fields)) {
            throw new Error("Call to undefined method ".__CLASS__."::$field()");
        }

        if (empty($arguments)) {
            throw new TypeError('Too few arguments to function '.__CLASS__."::$field(), 0 passed");
        }

        $this->fields[$field] = $arguments[0];

        return $this;
    }
}
