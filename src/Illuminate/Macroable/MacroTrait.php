<?php

namespace Illuminate\Support;

use ReflectionClass;

class MacroTrait
{
    protected $reflected;

    public function __construct($target)
    {
        $this->reflected = new ReflectionClass($target);
    }

    public function __get(string $name): mixed
    {
        $property = $this->reflected->getProperty($name);

        $property->setAccessible(true);

        return $property->getValue($this->target);
    }

    public function __set(string $name, mixed $value): void
    {
        $property = $this->reflected->getProperty($name);

        $property->setAccessible(true);

        $property->setValue($this->target, $value);
    }

    public function __call(string $name, array $params = []): mixed
    {
        $method = $this->reflected->getMethod($name);

        $method->setAccessible(true);

        return $method->invoke($this->target, ...$params);
    }
}
