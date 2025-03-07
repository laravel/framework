<?php

namespace Illuminate\Validation\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\NativeType;

class TypeRule implements ValidationRule
{
    protected ?NativeType $expectedType = null;

    public function array(): static
    {
        $this->expectedType = NativeType::Array;
        
        return $this;
    }

    public function bool(): static
    {
        $this->expectedType = NativeType::Bool;

        return $this;
    }

    public function float(): static
    {
        $this->expectedType = NativeType::Float;

        return $this;
    }

    public function int(): static
    {
        $this->expectedType = NativeType::Int;

        return $this;
    }

    public function numeric(): static
    {
        $this->expectedType = NativeType::Numeric;

        return $this;
    }

    public function scalar(): static
    {
        $this->expectedType = NativeType::Scalar;

        return $this;
    }

    public function string(): static
    {
        $this->expectedType = NativeType::String;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->passes($value)) {
            return;
        }

        $fail("The $attribute must be of type {$this->expectedType->value}");
    }

    protected function passes(mixed $value): bool
    {
        if ($this->expectedType === null) {
            throw new \RuntimeException('Type must be specified');
        }

        return match (true) {
            $this->expectedType === NativeType::Array => is_array($value),
            $this->expectedType === NativeType::Bool => is_bool($value),
            $this->expectedType === NativeType::Float => is_float($value),
            $this->expectedType === NativeType::Int => is_int($value),
            $this->expectedType === NativeType::Numeric => is_numeric($value),
            $this->expectedType === NativeType::Scalar => is_scalar($value),
            $this->expectedType === NativeType::String => is_string($value),
            default => false,
        };
    }
}
