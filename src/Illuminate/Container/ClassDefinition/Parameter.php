<?php

declare(strict_types=1);

namespace Illuminate\Container\ClassDefinition;

final readonly class Parameter
{
    public function __construct(
        public string $name,
        public ?string $className,
        public bool $isVariadic,
        public bool $isDefaultValueAvailable,
        public mixed $defaultValue,
        public string $declaringClassName,
        public string $asString,
    ) {
    }

    public function __toString(): string
    {
        return $this->asString;
    }
}
