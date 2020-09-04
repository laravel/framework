<?php

namespace Illuminate\Http\Concerns;

use Illuminate\Support\Str;

trait CastToObject
{
    /**
     * @var mixed
     */
    private $castClassVars;

    /**
     * Cast the request to specified class or object.
     * 
     * @param class $class
     * @param array $overwrite
     * @return object
     */
    public function castTo($class, $overwrite = [])
    {
        $object = is_object($class) ? $class : (new $class);

        foreach (array_replace($this->all(), $overwrite) as $property => $value) {

            // exact version.
            if ($this->propertyExistsAndWasPublic($object, $property)) {
                $object->$property = $value;
                continue;
            }

            // camel case version.
            if ($this->propertyExistsAndWasPublic($object, $property =  Str::camel($property))) {
                $object->$property = $value;
                continue;
            }

            // setter version.
            $methodName = "set" . Str::ucfirst((Str::camel($property)));
            if (method_exists($object, $methodName)) {
                $object->$methodName($value);
                continue;
            }
        }

        $this->castClassVars = null;

        return $object;
    }

    /**
     * @param mixed $object
     * @param string $property
     * @return boolean
     */
    private function isPublicProperty($object, $property)
    {
        $classVars = function ($object) {
            if ($this->castClassVars) {
                return $this->castClassVars;
            }

            return $this->castClassVars = array_keys(
                get_class_vars(
                    get_class($object)
                )
            );
        };

        if (in_array($property, $classVars($object))) {
            return true;
        }

        return false;
    }

    /**
     * @param object $object
     * @param string $property
     * @return bool
     */
    private function propertyExistsAndWasPublic($object, $property)
    {
        if (property_exists($object, $property) && $this->isPublicProperty($object, $property)) {
            return true;
        }

        return false;
    }
}
