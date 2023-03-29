<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasAttributes;
use PHPUnit\Framework\TestCase;

class DatabaseConcernsHasAttributesTest extends TestCase
{
    public function testWithoutConstructor()
    {
        $instance = new HasAttributesWithoutConstructor();
        $attributes = $instance->getMutatedAttributes();
        $this->assertEquals(['some_attribute'], $attributes);
        $this->assertEquals('some_value', $instance->getAttribute('some_attribute'));
    }

    public function testWithConstructorArguments()
    {
        $instance = new HasAttributesWithConstructorArguments(null);
        $attributes = $instance->getMutatedAttributes();
        $this->assertEquals(['some_attribute'], $attributes);
        $this->assertEquals('some_value', $instance->getAttribute('some_attribute'));
    }

    public function testWithUnionReturnType()
    {
        $instance = new HasAttributesWithUnionReturnType();
        $attributes = $instance->getMutatedAttributes();
        $this->assertEquals(['some_attribute'], $attributes);
        $this->assertEquals('some_value', $instance->getAttribute('some_attribute'));
        $this->assertEquals('other_value', $instance->someAttribute(true));
    }
}

class HasAttributesWithoutConstructor
{
    use HasAttributes;

    public function someAttribute(): Attribute
    {
        return new Attribute(function () {
            return 'some_value';
        });
    }
}

class HasAttributesWithConstructorArguments extends HasAttributesWithoutConstructor
{
    public function __construct($someValue)
    {
    }
}

class HasAttributesWithUnionReturnType
{
    use HasAttributes;

    public function someAttribute(bool $someArgument = false): Attribute|string
    {
        if ($someArgument) {
            return 'other_value';
        }

        return new Attribute(function () {
            return 'some_value';
        });
    }
}
