<?php

namespace Illuminate\Database\Query;

use InvalidArgumentException;

class JsonExpression extends Expression
{
    /**
     * The value of the expression.
     *
     * @var mixed
     */
    protected $value;

    /**
     * Create a new raw query expression.
     *
     * @param  mixed  $value
     * @return void
     */
    public function __construct($value)
    {
        $this->value = $this->getJsonBindingParameter($value);
    }

    /**
     * Translate the given value into the appropriate JSON binding parameter.
     *
     * @param  mixed  $value
     * @return string
     */
    protected function getJsonBindingParameter($value)
    {
        switch ($type = gettype($value)) {
            case 'boolean':
                return $value ? 'true' : 'false';
            case 'integer':
            case 'double':
                return $value;
            case 'string':
                return '?';
            case 'object':
            case 'array':
                return '?';
        }

        throw new InvalidArgumentException('JSON value is of illegal type: '.$type);
    }

    /**
     * Get the value of the expression.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Get the value of the expression.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getValue();
    }
}
