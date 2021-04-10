<?php

namespace Illuminate\Database\Eloquent\Relations\Concerns;

use Doctrine\Instantiator\Exception\InvalidArgumentException;

trait InteractsWithDictionary
{
    /**
     * Get a dictionary key attribute - casting it to a string if necessary.
     *
     * @param  mixed  $attribute
     * @return mixed
     */
    protected function getDictionaryKey($attribute)
    {
        if (is_object($attribute)) {
            if (method_exists($attribute, '__toString')) {
                return $attribute->__toString();
            }

            throw new InvalidArgumentException('Model attribute value is an object but does not have a __toString method.');
        }

        return $attribute;
    }
}
