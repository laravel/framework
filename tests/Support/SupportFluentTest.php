<?php

namespace Illuminate\Tests\Support;

use ArrayIterator;
use Illuminate\Support\Fluent;
use Illuminate\Tests\Support\Concerns\TestsInteractsWithDataContract;
use IteratorAggregate;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

class SupportFluentTest extends TestCase
{
    use TestsInteractsWithDataContract;

    public function testAttributesAreSetByConstructor()
    {
        $array = ['name' => 'Taylor', 'age' => 25];
        $fluent = new Fluent($array);

        $refl = new ReflectionObject($fluent);
        $attributes = $refl->getProperty('attributes');

        $this->assertEquals($array, $attributes->getValue($fluent));
        $this->assertEquals($array, $fluent->getAttributes());
    }

    public function testAttributesAreSetByConstructorGivenstdClass()
    {
        $array = ['name' => 'Taylor', 'age' => 25];
        $fluent = new Fluent((object) $array);

        $refl = new ReflectionObject($fluent);
        $attributes = $refl->getProperty('attributes');

        $this->assertEquals($array, $attributes->getValue($fluent));
        $this->assertEquals($array, $fluent->getAttributes());
    }

    public function testAttributesAreSetByConstructorGivenArrayIterator()
    {
        $array = ['name' => 'Taylor', 'age' => 25];
        $fluent = new Fluent(new FluentArrayIteratorStub($array));

        $refl = new ReflectionObject($fluent);
        $attributes = $refl->getProperty('attributes');

        $this->assertEquals($array, $attributes->getValue($fluent));
        $this->assertEquals($array, $fluent->getAttributes());
    }

    public function testGetMethodReturnsAttribute()
    {
        $fluent = new Fluent(['name' => 'Taylor']);

        $this->assertSame('Taylor', $fluent->get('name'));
        $this->assertSame('Default', $fluent->get('foo', 'Default'));
        $this->assertSame('Taylor', $fluent->name);
        $this->assertNull($fluent->foo);
    }

    public function testSetMethodSetsAttribute()
    {
        $fluent = new Fluent;

        $fluent->set('name', 'Taylor');
        $fluent->set('developer', true);
        $fluent->set('posts', 25);
        $fluent->set('computer.color', 'silver');

        $this->assertSame('Taylor', $fluent->name);
        $this->assertTrue($fluent->developer);
        $this->assertSame(25, $fluent->posts);
        $this->assertSame(['color' => 'silver'], $fluent->computer);
    }

    public function testArrayAccessToAttributes()
    {
        $fluent = new Fluent(['attributes' => '1']);

        $this->assertTrue(isset($fluent['attributes']));
        $this->assertEquals(1, $fluent['attributes']);

        $fluent->attributes();

        $this->assertTrue($fluent['attributes']);
    }

    public function testMagicMethodsCanBeUsedToSetAttributes()
    {
        $fluent = new Fluent;

        $fluent->name = 'Taylor';
        $fluent->developer();
        $fluent->age(25);

        $this->assertSame('Taylor', $fluent->name);
        $this->assertTrue($fluent->developer);
        $this->assertEquals(25, $fluent->age);
        $this->assertInstanceOf(Fluent::class, $fluent->programmer());
    }

    public function testIssetMagicMethod()
    {
        $array = ['name' => 'Taylor', 'age' => 25];
        $fluent = new Fluent($array);

        $this->assertTrue(isset($fluent->name));

        unset($fluent->name);

        $this->assertFalse(isset($fluent->name));
    }

    public function testToArrayReturnsAttribute()
    {
        $array = ['name' => 'Taylor', 'age' => 25];
        $fluent = new Fluent($array);

        $this->assertEquals($array, $fluent->toArray());
    }

    public function testToJsonEncodesTheToArrayResult()
    {
        $fluent = new Fluent(['foo']);
        $results = $fluent->toJson();

        $this->assertJsonStringEqualsJsonString(json_encode(['foo']), $results);
    }

    public function testToPrettyJson()
    {
        $fluent = new Fluent(['foo' => 'bar', 'bar' => 'foo']);
        $results = $fluent->toPrettyJson();
        $expected = $fluent->toJson(JSON_PRETTY_PRINT);

        $this->assertJsonStringEqualsJsonString($expected, $results);
        $this->assertSame($expected, $results);
        $this->assertStringContainsString("\n", $results);
        $this->assertStringContainsString('    ', $results);
    }

    public function testScope()
    {
        $fluent = new Fluent(['user' => ['name' => 'taylor']]);
        $this->assertEquals(['taylor'], $fluent->scope('user.name')->toArray());
        $this->assertEquals(['dayle'], $fluent->scope('user.age', 'dayle')->toArray());

        $fluent = new Fluent(['products' => ['forge', 'vapour', 'spark']]);
        $this->assertEquals(['forge', 'vapour', 'spark'], $fluent->scope('products')->toArray());
        $this->assertEquals(['foo', 'bar'], $fluent->scope('missing', ['foo', 'bar'])->toArray());

        $fluent = new Fluent(['authors' => ['taylor' => ['products' => ['forge', 'vapour', 'spark']]]]);
        $this->assertEquals(['forge', 'vapour', 'spark'], $fluent->scope('authors.taylor.products')->toArray());
    }

    public function testToCollection()
    {
        $fluent = new Fluent(['forge', 'vapour', 'spark']);
        $this->assertEquals(['forge', 'vapour', 'spark'], $fluent->collect()->all());

        $fluent = new Fluent(['authors' => ['taylor' => ['products' => ['forge', 'vapour', 'spark']]]]);
        $this->assertEquals(['forge', 'vapour', 'spark'], $fluent->collect('authors.taylor.products')->all());
    }

    public function testStringMethod()
    {
        $this->assertDataStringContract();
    }

    public function testBooleanMethod()
    {
        $this->assertDataBooleanContract();
    }

    public function testIntegerMethod()
    {
        $this->assertDataIntegerContract();
    }

    public function testFloatMethod()
    {
        $this->assertDataFloatContract();
    }

    public function testArrayMethod()
    {
        $this->assertDataArrayContract();
        $this->assertDataArrayArrayableContract();
    }

    public function testCollectMethod()
    {
        $this->assertDataCollectContract();
        $this->assertDataCollectNestedContract();
    }

    public function testDateMethod()
    {
        $this->assertDataDateContract();
    }

    public function testEnumMethod()
    {
        $this->assertDataEnumContract();
        $this->assertDataEnumNestedContract();
    }

    public function testEnumsMethod()
    {
        $this->assertDataEnumsContract();
    }

    public function testFill()
    {
        $fluent = new Fluent(['name' => 'John Doe']);

        $fluent->fill([
            'email' => 'john.doe@example.com',
            'age' => 30,
        ]);

        $this->assertEquals([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'age' => 30,
        ], $fluent->getAttributes());
    }

    public function testMacroable()
    {
        Fluent::macro('foo', function () {
            return $this->fill([
                'foo' => 'bar',
                'baz' => 'zal',
            ]);
        });

        $fluent = new Fluent([
            'bee' => 'ser',
        ]);

        $this->assertSame([
            'bee' => 'ser',
            'foo' => 'bar',
            'baz' => 'zal',
        ], $fluent->foo()->all());
    }

    public function testFluentIsIterable()
    {
        $fluent = new Fluent([
            'name' => 'Taylor',
            'role' => 'admin',
        ]);

        $result = [];

        foreach ($fluent as $key => $value) {
            $result[$key] = $value;
        }

        $this->assertSame([
            'name' => 'Taylor',
            'role' => 'admin',
        ], $result);
    }

    public function testFluentIsEmpty()
    {
        $fluent = new Fluent;

        $this->assertTrue($fluent->isEmpty());
        $this->assertFalse($fluent->isNotEmpty());
    }

    public function testFluentIsNotEmpty()
    {
        $fluent = new Fluent([
            'name' => 'Taylor',
            'role' => 'admin',
        ]);

        $this->assertTrue($fluent->isNotEmpty());
        $this->assertFalse($fluent->isEmpty());
    }

    public function testExistsMethod()
    {
        $this->assertDataExistsContract();
        $this->assertDataExistsNestedContract();
    }

    public function testHasMethod()
    {
        $this->assertDataHasContract();
        $this->assertDataHasNestedContract();
    }

    public function testHasAnyMethod()
    {
        $this->assertDataHasAnyContract();
        $this->assertDataHasAnyNestedContract();
    }

    public function testWhenHasMethod()
    {
        $this->assertDataWhenHasContract();
        $this->assertDataWhenHasNestedContract();
    }

    public function testFilledMethod()
    {
        $this->assertDataFilledContract();
        $this->assertDataFilledNestedContract();
    }

    public function testIsNotFilledMethod()
    {
        $this->assertDataIsNotFilledContract();
        $this->assertDataIsNotFilledNestedContract();
    }

    public function testAnyFilledMethod()
    {
        $this->assertDataAnyFilledContract();
        $this->assertDataAnyFilledNestedContract();
    }

    public function testWhenFilledMethod()
    {
        $this->assertDataWhenFilledContract();
        $this->assertDataWhenFilledNestedContract();
    }

    public function testWhenEnumMethod()
    {
        $this->assertDataWhenEnumContract();
    }

    public function testMissingMethod()
    {
        $this->assertDataMissingContract();
        $this->assertDataMissingNestedContract();
    }

    public function testWhenMissingMethod()
    {
        $this->assertDataWhenMissingContract();
        $this->assertDataWhenMissingNestedContract();
    }

    public function testStrMethod()
    {
        $this->assertDataStrContract();
    }

    public function testClampMethod()
    {
        $this->assertDataClampContract();
    }

    public function testIntervalMethod()
    {
        $this->assertDataIntervalContract();
    }

    public function testOnlyMethod()
    {
        $this->assertDataOnlyContract();
        $this->assertDataOnlyNestedContract();
    }

    public function testExceptMethod()
    {
        $this->assertDataExceptContract();
        $this->assertDataExceptNestedContract();
    }

    public function testNullValues()
    {
        $this->assertDataNullValuesContract();
        $this->assertDataNullValuesIgnoreDefaultsContract();
        $this->assertDataNestedNullValuesContract();
    }

    protected static function arrayToDataFactory(array $data)
    {
        return new Fluent($data);
    }
}

class FluentArrayIteratorStub implements IteratorAggregate
{
    protected $items = [];

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }
}
