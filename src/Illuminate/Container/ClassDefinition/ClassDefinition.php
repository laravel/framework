<?php

declare(strict_types=1);

namespace Illuminate\Container\ClassDefinition;

final readonly class ClassDefinition
{
    /**
     * @param  Parameter[]  $parameters
     */
    public function __construct(
        public string $class,
        public bool $isInstantiable,
        public bool $isConstructorDefined,
        public array $parameters,
    ) {
    }
}
