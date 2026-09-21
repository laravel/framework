<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
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

    public function testAttributesMarkedAsAppendableAreAppended()
    {
        $instance = new HasAppendableAttributes();

        $this->assertSame(['full_name'], $instance->getAppends());
        $this->assertSame(['full_name' => 'Taylor Otwell'], $instance->toArray());
    }

    public function testAppendableAttributesAreMergedWithTheAppendsProperty()
    {
        $instance = new HasAppendableAndAppendedAttributes();

        $this->assertSame(['initials', 'full_name'], $instance->getAppends());
    }

    public function testAppendableAttributesCanBeRemoved()
    {
        $instance = new HasAppendableAttributes();

        $this->assertSame([], $instance->withoutAppends()->toArray());
    }

    public function testAppendableAttributesAreInheritedFromParentsAndTraits()
    {
        $instance = new HasInheritedAppendableAttributes();

        $this->assertEqualsCanonicalizing(['full_name', 'nickname'], $instance->getAppends());
    }

    public function testAttributesWithoutAccessorsAreNotAppended()
    {
        $instance = new HasAppendableMutatorOnly();

        $this->assertSame([], $instance->getAppends());
    }
}

class HasAppendableAttributes extends Model
{
    public function fullName(): Attribute
    {
        return Attribute::get(fn () => 'Taylor Otwell')->shouldAppend();
    }
}

trait HasNickname
{
    protected function nickname(): Attribute
    {
        return Attribute::get(fn () => 'Taylor')->shouldAppend();
    }
}

class HasInheritedAppendableAttributes extends HasAppendableAttributes
{
    use HasNickname;
}

class HasAppendableMutatorOnly extends Model
{
    protected function fullName(): Attribute
    {
        return Attribute::set(fn ($value) => $value)->shouldAppend();
    }
}

class HasAppendableAndAppendedAttributes extends Model
{
    protected $appends = ['initials'];

    public function initials(): Attribute
    {
        return Attribute::get(fn () => 'TO');
    }

    public function fullName(): Attribute
    {
        return Attribute::get(fn () => 'Taylor Otwell')->shouldAppend();
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
