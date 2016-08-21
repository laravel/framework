<?php

namespace Illuminate\Container;

use Reflector;
use ReflectionClass;
use ReflectionMethod;
use ReflectionFunction;
use ReflectionParameter;

class Resolver
{
	const TYPE_CLASS = 1;
	const TYPE_METHOD = 2;
	const TYPE_FUNCTION = 3;

	private $RESOLVERS_MAP = [
		self::TYPE_CLASS => "resolveClass",
		self::TYPE_METHOD => "resolveMethod",
		self::TYPE_FUNCTION => "resolveFunction"
	];

	private function resolveParameters(array $reflectionParameters, array $parameters = [])
    {
    	$i = 0;
        $resolvedParameters = [];

        foreach ($reflectionParameters as $parameter) {
            if (($class = $parameter->getClass())) {
                $resolvedParameters[] = $this->resolve($class->getName());
            } else {
                $resolvedParameters[] = $parameters[$i++];
            }
        }

        return $resolvedParameters;
    }

    public function resolveClass($class, array $parameters = [])
    {
        $reflectionClass = new ReflectionClass($class);

        if (($reflectionMethod = $reflectionClass->getConstructor())) {
        	$reflectionParameters = $reflectionMethod->getParameters();

        	$resolvedParameters = $this->resolveParameters($reflectionParameters, $parameters);

        	return $reflectionClass->newInstanceArgs($resolvedParameters);
        }

        return $reflectionClass->newInstanceArgs();
    }

    public function resolveMethod($method, array $parameters = [])
    {
        $reflectionMethod = new ReflectionMethod($method[0], $method[1]);
        $reflectionParameters = $reflectionMethod->getParameters();

        $resolvedParameters = $this->resolveParameters($reflectionParameters, $parameters);

        return $reflectionMethod->invokeArgs($method[0], $resolvedParameters);
    }

    public function resolveFunction($function, array $parameters = [])
    {
        $reflectionFunction = new ReflectionFunction($function);
        $reflectionParameters = $reflectionFunction->getParameters();

        $resolvedParameters = $this->resolveParameters($reflectionParameters, $parameters);

        return $reflectionFunction->invokeArgs($resolvedParameters);
    }

    public function resolve($subject, array $parameters = [], $type = null)
    {
    	$type = ($type !== null) ? $type : self::getType($subject);

    	if ($type) {
	    	$resolver = $this->RESOLVERS_MAP[$type];

	    	return call_user_func_array([$this, $resolver], [$subject, $parameters]);
    	}

        return null;
    }

    public static function getType($subject)
    {
        if (self::isClass($subject)) {
        	return self::TYPE_CLASS;
        } else if (self::isMethod($subject)) {
        	return self::TYPE_METHOD;
        } else if (self::isFunction($subject)) {
        	return self::TYPE_FUNCTION;
        }

        return null;
    }

    public static function isClass($subject)
    {
    	return !is_callable($subject) && is_string($subject);
    }

    public static function isMethod($subject)
    {
    	return is_callable($subject) && is_array($subject);
    }

    public static function isFunction($subject)
    {
    	return is_callable($subject) && is_string($subject);
    }
}
