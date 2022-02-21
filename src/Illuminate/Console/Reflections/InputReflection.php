<?php

namespace Illuminate\Console\Reflections;

use Illuminate\Contracts\Console\ConsoleInput;
use Illuminate\Tests\Console\StringEnum;
use Illuminate\Validation\Rules\Enum;

abstract class InputReflection
{
    public function __construct(
        protected \ReflectionProperty $property,
        protected ConsoleInput $consoleInput
    ) {
    }

    public function getName(): string
    {
        return $this->property->getName();
    }

    public function getAlias(): ?string
    {
        return $this->consoleInput->getAlias();
    }

    public function getDescription(): string
    {
        return $this->consoleInput->getDescription();
    }

    public function getDefaultValue(): string|bool|int|float|array|null
    {
        return $this->property->hasDefaultValue()
            ? $this->castFrom($this->property->getDefaultValue())
            : null;
    }

    public function isOptional(): bool
    {
        return $this->property->hasDefaultValue() || $this->property->getType()?->allowsNull();
    }

    public function isArray(): bool
    {
        if (($type = $this->property->getType()) instanceof \ReflectionNamedType) {
            return $type->getName() === 'array';
        }

        return false;
    }

    public function castFrom(mixed $value): int|float|array|string|bool|null
    {
        return match (gettype($value)) {
            'integer', 'NULL', 'boolean', 'double', 'string', 'array' => $value,
            'object' => function_exists('enum_exists') && enum_exists($value::class) ? $this->castEnum($value) : $value,
            default => $value,
        };
    }

    public function castEnum(object $value): int|float|array|string|bool|null
    {
        return (new \ReflectionEnum($value))->isBacked()
            ? $value->value
            : $value->name;
    }

    public function castTo(int|array|float|string|bool|null $value): mixed
    {
        if (! ($type = $this->property->getType())) {
            return $value;
        }

        if (! $type instanceof \ReflectionNamedType) {
            return $value;
        }

        if (! function_exists('enum_exists')){
            return $value;
        }

        if (! enum_exists($type->getName())){
            return $value;
        }

        $enum = new \ReflectionEnum($type->getName());

        return $enum->isBacked()
            ? ($type->getName())::from((string) $value)
            : $enum->getCase((string) $value)->getValue();
    }
}
