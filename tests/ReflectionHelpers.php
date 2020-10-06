<?php

namespace Illuminate\Tests;

use ReflectionClass;
use ReflectionException;
use ReflectionObject;

trait ReflectionHelpers
{
    /**
     * Try to call private and protected methods in given object.
     *
     * @param $obj
     * @param $name
     * @param array $args
     * @return mixed
     *
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
     * Get private and protected property from given object.
     *
     * @param $obj
     * @param $name
     * @return mixed
     *
     * @throws ReflectionException
     */
    protected static function getPrivateProperty($obj, $name)
    {
        $class = new ReflectionObject($obj);
        $property = $class->getProperty($name);
        $property->setAccessible(true);

        return $property->getValue($obj);
    }
}
