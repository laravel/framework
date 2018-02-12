<?php

namespace Illuminate\Database\Query;

use InvalidArgumentException;

class JsonExpression extends Expression
{
    /**
     * Create a new raw query expression.
     *
     * @param  mixed  $value
     * @return void
     */
    public function __construct($value)
    {
        parent::__construct(
            $this->getJsonBindingParameter($value)
        );
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
}
