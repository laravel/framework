<?php

declare(strict_types=1);

namespace Illuminate\Container\ClassDefinition;

use Illuminate\Container\Util;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

final class ClassDefinitionProvider
{
    /**
     * @var array<string, ClassDefinition>
     */
    private array $definitions = [];

    /**
     * @throws ReflectionException
     */
    public function get(string $className): ClassDefinition
    {
        if (isset($this->definitions[$className])) {
            return $this->definitions[$className];
        }

        $reflection = new ReflectionClass($className);
        $definition = new ClassDefinition(
            class: $className,
            isInstantiable: $reflection->isInstantiable(),
            isConstructorDefined: ! is_null($reflection->getConstructor()),
            parameters: array_map(
                fn (ReflectionParameter $parameter) => new Parameter(
                    name: $parameter->getName(),
                    className: Util::getParameterClassName($parameter),
                    isVariadic: $parameter->isVariadic(),
                    isDefaultValueAvailable: $parameter->isDefaultValueAvailable(),
                    defaultValue: $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null,
                    declaringClassName: $parameter->getDeclaringClass()->getName(),
                    asString: (string) $parameter,
                ),
                $reflection->getConstructor()?->getParameters() ?? [],
            ),
        );

        $this->definitions[$className] = $definition;

        return $definition;
    }
}
