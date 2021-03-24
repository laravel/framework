<?php

namespace Illuminate\Database\Eloquent\Relations\Concerns;

use Doctrine\Instantiator\Exception\InvalidArgumentException;

trait InteractsWithDictionary
{
    /**
     * @param mixed $attribute
     * @return mixed
     */
    protected function getDictionaryKey($attribute)
    {
        if (is_object($attribute)) {
            if (method_exists($attribute, '__toString')) {
                return $attribute->__toString();
            }
            throw new InvalidArgumentException('Attribute value is an object without __toString method'); //I would prefer to throw an exception instead of "silent" and unintended behaviour
        }

        return $attribute;
    }
}
