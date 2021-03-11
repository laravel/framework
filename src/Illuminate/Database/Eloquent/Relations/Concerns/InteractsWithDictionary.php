<?php

namespace Illuminate\Database\Eloquent\Relations\Concerns;

use InvalidArgumentException;

trait InteractsWithDictionary
{
    /**
     * @param mixed $attribute
     * @return mixed
     */
    protected function dictionaryKey($attribute)
    {
        if (is_object($attribute)) {
            if (method_exists($attribute, '__toString')) {
                return $attribute->__toString();
            }
            throw new InvalidArgumentException('Attribute value is an object without __toString method');
        }

        return $attribute;
    }
}
