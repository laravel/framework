<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Support\NativeType;
use Stringable;

class TypeRule implements Stringable
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

    public function __toString(): string
    {
        if ($this->expectedType === null) {
            throw new \RuntimeException('Type rule must have a type specified.');
        }

        return 'type:'.$this->expectedType->value;
    }
}
