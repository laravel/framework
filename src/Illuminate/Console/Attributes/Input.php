<?php

namespace Illuminate\Console\Attributes;

use Illuminate\Console\Command;
use ReflectionParameter;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use TypeError;

abstract class Input
{
    public function __construct(public string $parameter)
    {
    }

    /**
     * Resolve the input.
     *
     * @param  self  $attribute
     * @return mixed
     *
     * @throws \TypeError on type mismatch
     */
    public static function resolve(self $attribute, Command $command, ReflectionParameter $parameter)
    {
        $input = $attribute->getInput($command, $attribute->parameter);
        $type = $parameter->getType();

        if (! $this->checkType($type, $input)) {
            throw new TypeError(sprintf('"%s" is not of type string. %s given', $attribute->parameter, get_debug_type($input)));
        }
      
        return $input;
    }

    private function checkType(ReflectionType $expectedType, $actualValue): bool
    {
        return match (true) {
            $expectedType instanceof ReflectionIntersectionType => array_all($expectedType->getTypes(), $this->checkType(...)),
            $expectedType instanceof ReflectionUnionType => array_any($expectedType->getTypes(), $this->checkType(...)),
            $expectedType instanceof ReflectionNamedType => class_exists($expectedType) ? $actualValue instanceof $expectedType : get_debug_type($actualValue) === $expectedType->getName(),
        };
    }

    /**
     * @param \Illuminate\Console\Command $command
     *
     * @throws \InvalidArgumentException when neither an option nor an argument
     *                                   with give key exists and no default value was given
     */
    abstract protected function getInput(Command $command, string $parameter);
}
