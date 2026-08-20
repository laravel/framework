<?php

namespace Illuminate\Tests\Database;

use Illuminate\Support\Collection;
use Illuminate\Tests\App\Models\Casts\HasAttributesWithArrayCast;
use Illuminate\Tests\App\Models\Casts\HasAttributesWithConstructorArguments;
use Illuminate\Tests\App\Models\Casts\HasAttributesWithoutConstructor;
use Illuminate\Tests\App\Models\Casts\HasCacheableAttributeWithAccessor;
use Mockery;
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
        $mock = Mockery::mock(HasAttributesWithoutConstructor::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->expects('getArrayableRelations')->andReturn([
                'arrayable_relation' => new Collection(['foo' => 'bar']),
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

        $this->assertSame(JSON_ERROR_NONE, json_last_error());
    }

    public function testUnsettingCachedAttribute()
    {
        $instance = new HasCacheableAttributeWithAccessor();
        $this->assertSame('foo', $instance->getAttribute('cacheableProperty'));
        $this->assertTrue($instance->cachedAttributeIsset('cacheableProperty'));

        unset($instance->cacheableProperty);

        $this->assertFalse($instance->cachedAttributeIsset('cacheableProperty'));
    }
}
