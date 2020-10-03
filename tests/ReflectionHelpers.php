<?php

namespace Illuminate\Tests;

use ReflectionClass;
use ReflectionException;
use ReflectionObject;

trait ReflectionHelpers
{
    /**
     * @param $obj
     * @param $name
     * @param array $args
     * @return mixed
     * @throws ReflectionException
     */
    protected static function privateCall($obj, $name, array $args = [])
    {
        $class = new ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($obj, $args);
    }

    /**
     * @param $obj
     * @param $name
     * @return mixed
     * @throws ReflectionException
     */
    protected static function getPrivateProperty($obj, $name)
    {
        $class = new ReflectionObject($obj);
        $property = $class->getProperty($name);
        $property->setAccessible(true);

        return $property->getValue($obj);
    }

    /**
     * @param mixed $object
     * @param mixed $property
     * @param $value
     * @throws ReflectionException
     */
    protected function setPrivateProperty($object, $property, $value)
    {
        $reflection = new ReflectionClass($object);
        $reflectionProperty = $reflection->getProperty($property);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($object, $value);
    }
}
