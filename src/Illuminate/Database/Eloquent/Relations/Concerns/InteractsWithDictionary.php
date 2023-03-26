<?php

namespace Illuminate\Database\Eloquent\Relations\Concerns;

use BackedEnum;
use Doctrine\Instantiator\Exception\InvalidArgumentException;
use UnitEnum;

trait InteractsWithDictionary
{
    /**
     * Get a dictionary key attribute - casting it to a string if necessary.
     *
     * @param  mixed  $attribute
     * @return mixed
     *
     * @throws \Doctrine\Instantiator\Exception\InvalidArgumentException
     */
    protected function getDictionaryKey($attribute)
    {
        if (is_object($attribute)) {
            if (method_exists($attribute, '__toString')) {
                return $attribute->__toString();
            }

            if ($attribute instanceof UnitEnum) {
                return $this->getEnumValue($attribute);
            }

            throw new InvalidArgumentException('Model attribute value is an object but does not have a __toString method.');
        }

        return $attribute;
    }

    /**
     * Get a value from an enum case if it is backed, otherwise use the case name.
     *
     * @param  UnitEnum  $attribute
     * @return string | int
     */
    protected function getEnumValue(UnitEnum $attribute)
    {
        return $attribute instanceof BackedEnum ? $attribute->value : $attribute->name;
    }
}
