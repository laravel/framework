<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseConcernsHasAttributesTest extends TestCase
{
    public function testWithoutConstructor()
    {
        $instance = new HasAttributesWithoutConstructor();
        $attributes = $instance->getMutatedAttributes();
        $this->assertEquals(['some_attribute'], $attributes);
    }

    public function testWithConstructorArguments()
    {
        $instance = new HasAttributesWithConstructorArguments(null);
        $attributes = $instance->getMutatedAttributes();
        $this->assertEquals(['some_attribute'], $attributes);
    }

    public function testRelationsToArray()
    {
        $mock = m::mock(HasAttributesWithoutConstructor::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('getArrayableRelations')->andReturn([
                'arrayable_relation' => Collection::make(['foo' => 'bar']),
                'invalid_relation' => 'invalid',
                'null_relation' => null,
            ])
            ->getMock();

        $this->assertEquals([
            'arrayable_relation' => ['foo' => 'bar'],
            'null_relation' => null,
        ], $mock->relationsToArray());
    }

    public function testCastingEmptyStringToArrayDoesNotError()
    {
        $instance = new HasAttributesWithArrayCast();
        $this->assertEquals(['foo' => null], $instance->attributesToArray());

        $this->assertTrue(json_last_error() === JSON_ERROR_NONE);
    }

    public function testUnsettingCachedAttribute()
    {
        $instance = new HasCacheableAttributeWithAccessor();
        $this->assertEquals('foo', $instance->getAttribute('cacheableProperty'));
        $this->assertTrue($instance->cachedAttributeIsset('cacheableProperty'));

        unset($instance->cacheableProperty);

        $this->assertFalse($instance->cachedAttributeIsset('cacheableProperty'));
    }
}

class HasAttributesWithoutConstructor
{
    use HasAttributes;

    public function someAttribute(): Attribute
    {
        return new Attribute(function () {
        });
    }
}

class HasAttributesWithConstructorArguments extends HasAttributesWithoutConstructor
{
    public function __construct($someValue)
    {
    }
}

class HasAttributesWithArrayCast
{
    use HasAttributes;

    public function getArrayableAttributes(): array
    {
        return ['foo' => ''];
    }

    public function getCasts(): array
    {
        return ['foo' => 'array'];
    }

    public function usesTimestamps(): bool
    {
        return false;
    }
}

/**
 * @property string $cacheableProperty
 */
class HasCacheableAttributeWithAccessor extends Model
{
    public function cacheableProperty(): Attribute
    {
        return Attribute::make(
            get: fn () => 'foo'
        )->shouldCache();
    }

    public function cachedAttributeIsset($attribute): bool
    {
        return isset($this->attributeCastCache[$attribute]);
    }
}
