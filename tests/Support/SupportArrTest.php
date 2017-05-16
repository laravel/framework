<?php

namespace Illuminate\Tests\Support;

use stdClass;
use ArrayObject;
use Illuminate\Support\Arr;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\Collection;

class SupportArrTest extends TestCase
{
    public function testAccessible()
    {
        $this->assertTrue(Arr::accessible([]));
        $this->assertTrue(Arr::accessible([1, 2]));
        $this->assertTrue(Arr::accessible(['a' => 1, 'b' => 2]));
        $this->assertTrue(Arr::accessible(new Collection));

        $this->assertFalse(Arr::accessible(null));
        $this->assertFalse(Arr::accessible('abc'));
        $this->assertFalse(Arr::accessible(new stdClass));
        $this->assertFalse(Arr::accessible((object) ['a' => 1, 'b' => 2]));
    }

    public function testAdd()
    {
        $array = Arr::add(['name' => 'Desk'], 'price', 100);
        $this->assertEquals(['name' => 'Desk', 'price' => 100], $array);
    }

    public function testCollapse()
    {
        $data = [['foo', 'bar'], ['baz']];
        $this->assertEquals(['foo', 'bar', 'baz'], Arr::collapse($data));
    }

    public function testCrossJoin()
    {
        // Single dimension
        $this->assertEquals(
            [[1, 'a'], [1, 'b'], [1, 'c']],
            Arr::crossJoin([1], ['a', 'b', 'c'])
        );

        // Square matrix
        $this->assertEquals(
            [[1, 'a'], [1, 'b'], [2, 'a'], [2, 'b']],
            Arr::crossJoin([1, 2], ['a', 'b'])
        );

        // Rectangular matrix
        $this->assertEquals(
            [[1, 'a'], [1, 'b'], [1, 'c'], [2, 'a'], [2, 'b'], [2, 'c']],
            Arr::crossJoin([1, 2], ['a', 'b', 'c'])
        );

        // 3D matrix
        $this->assertEquals(
            [
                [1, 'a', 'I'], [1, 'a', 'II'], [1, 'a', 'III'],
                [1, 'b', 'I'], [1, 'b', 'II'], [1, 'b', 'III'],
                [2, 'a', 'I'], [2, 'a', 'II'], [2, 'a', 'III'],
                [2, 'b', 'I'], [2, 'b', 'II'], [2, 'b', 'III'],
            ],
            Arr::crossJoin([1, 2], ['a', 'b'], ['I', 'II', 'III'])
        );

        // With 1 empty dimension
        $this->assertEquals([], Arr::crossJoin([1, 2], [], ['I', 'II', 'III']));
    }

    public function testDivide()
    {
        list($keys, $values) = Arr::divide(['name' => 'Desk']);
        $this->assertEquals(['name'], $keys);
        $this->assertEquals(['Desk'], $values);
    }

    public function testDot()
    {
        $array = Arr::dot(['foo' => ['bar' => 'baz']]);
        $this->assertEquals(['foo.bar' => 'baz'], $array);

        $array = Arr::dot([]);
        $this->assertEquals([], $array);

        $array = Arr::dot(['foo' => []]);
        $this->assertEquals(['foo' => []], $array);

        $array = Arr::dot(['foo' => ['bar' => []]]);
        $this->assertEquals(['foo.bar' => []], $array);
    }

    public function testExcept()
    {
        $array = ['name' => 'Desk', 'price' => 100];
        $array = Arr::except($array, ['price']);
        $this->assertEquals(['name' => 'Desk'], $array);
    }

    public function testExists()
    {
        $this->assertTrue(Arr::exists([1], 0));
        $this->assertTrue(Arr::exists([null], 0));
        $this->assertTrue(Arr::exists(['a' => 1], 'a'));
        $this->assertTrue(Arr::exists(['a' => null], 'a'));
        $this->assertTrue(Arr::exists(new Collection(['a' => null]), 'a'));

        $this->assertFalse(Arr::exists([1], 1));
        $this->assertFalse(Arr::exists([null], 1));
        $this->assertFalse(Arr::exists(['a' => 1], 0));
        $this->assertFalse(Arr::exists(new Collection(['a' => null]), 'b'));
    }

    public function testFirst()
    {
        $array = [100, 200, 300];

        $value = Arr::first($array, function ($value) {
            return $value >= 150;
        });

        $this->assertEquals(200, $value);
        $this->assertEquals(100, Arr::first($array));
    }

    public function testLast()
    {
        $array = [100, 200, 300];

        $last = Arr::last($array, function ($value) {
            return $value < 250;
        });
        $this->assertEquals(200, $last);

        $last = Arr::last($array, function ($value, $key) {
            return $key < 2;
        });
        $this->assertEquals(200, $last);

        $this->assertEquals(300, Arr::last($array));
    }

    public function testFlatten()
    {
        // Flat arrays are unaffected
        $array = ['#foo', '#bar', '#baz'];
        $this->assertEquals(['#foo', '#bar', '#baz'], Arr::flatten(['#foo', '#bar', '#baz']));

        // Nested arrays are flattened with existing flat items
        $array = [['#foo', '#bar'], '#baz'];
        $this->assertEquals(['#foo', '#bar', '#baz'], Arr::flatten($array));

        // Flattened array includes "null" items
        $array = [['#foo', null], '#baz', null];
        $this->assertEquals(['#foo', null, '#baz', null], Arr::flatten($array));

        // Sets of nested arrays are flattened
        $array = [['#foo', '#bar'], ['#baz']];
        $this->assertEquals(['#foo', '#bar', '#baz'], Arr::flatten($array));

        // Deeply nested arrays are flattened
        $array = [['#foo', ['#bar']], ['#baz']];
        $this->assertEquals(['#foo', '#bar', '#baz'], Arr::flatten($array));

        // Nested arrays are flattened alongside arrays
        $array = [new Collection(['#foo', '#bar']), ['#baz']];
        $this->assertEquals(['#foo', '#bar', '#baz'], Arr::flatten($array));

        // Nested arrays containing plain arrays are flattened
        $array = [new Collection(['#foo', ['#bar']]), ['#baz']];
        $this->assertEquals(['#foo', '#bar', '#baz'], Arr::flatten($array));

        // Nested arrays containing arrays are flattened
        $array = [['#foo', new Collection(['#bar'])], ['#baz']];
        $this->assertEquals(['#foo', '#bar', '#baz'], Arr::flatten($array));

        // Nested arrays containing arrays containing arrays are flattened
        $array = [['#foo', new Collection(['#bar', ['#zap']])], ['#baz']];
        $this->assertEquals(['#foo', '#bar', '#zap', '#baz'], Arr::flatten($array));
    }

    public function testFlattenWithDepth()
    {
        // No depth flattens recursively
        $array = [['#foo', ['#bar', ['#baz']]], '#zap'];
        $this->assertEquals(['#foo', '#bar', '#baz', '#zap'], Arr::flatten($array));

        // Specifying a depth only flattens to that depth
        $array = [['#foo', ['#bar', ['#baz']]], '#zap'];
        $this->assertEquals(['#foo', ['#bar', ['#baz']], '#zap'], Arr::flatten($array, 1));

        $array = [['#foo', ['#bar', ['#baz']]], '#zap'];
        $this->assertEquals(['#foo', '#bar', ['#baz'], '#zap'], Arr::flatten($array, 2));
    }

    public function testGet()
    {
        $array = ['products.desk' => ['price' => 100]];
        $this->assertEquals(['price' => 100], Arr::get($array, 'products.desk'));

        $array = ['products' => ['desk' => ['price' => 100]]];
        $value = Arr::get($array, 'products.desk');
        $this->assertEquals(['price' => 100], $value);

        // Test null array values
        $array = ['foo' => null, 'bar' => ['baz' => null]];
        $this->assertNull(Arr::get($array, 'foo', 'default'));
        $this->assertNull(Arr::get($array, 'bar.baz', 'default'));

        // Test direct ArrayAccess object
        $array = ['products' => ['desk' => ['price' => 100]]];
        $arrayAccessObject = new ArrayObject($array);
        $value = Arr::get($arrayAccessObject, 'products.desk');
        $this->assertEquals(['price' => 100], $value);

        // Test array containing ArrayAccess object
        $arrayAccessChild = new ArrayObject(['products' => ['desk' => ['price' => 100]]]);
        $array = ['child' => $arrayAccessChild];
        $value = Arr::get($array, 'child.products.desk');
        $this->assertEquals(['price' => 100], $value);

        // Test array containing multiple nested ArrayAccess objects
        $arrayAccessChild = new ArrayObject(['products' => ['desk' => ['price' => 100]]]);
        $arrayAccessParent = new ArrayObject(['child' => $arrayAccessChild]);
        $array = ['parent' => $arrayAccessParent];
        $value = Arr::get($array, 'parent.child.products.desk');
        $this->assertEquals(['price' => 100], $value);

        // Test missing ArrayAccess object field
        $arrayAccessChild = new ArrayObject(['products' => ['desk' => ['price' => 100]]]);
        $arrayAccessParent = new ArrayObject(['child' => $arrayAccessChild]);
        $array = ['parent' => $arrayAccessParent];
        $value = Arr::get($array, 'parent.child.desk');
        $this->assertNull($value);

        // Test missing ArrayAccess object field
        $arrayAccessObject = new ArrayObject(['products' => ['desk' => null]]);
        $array = ['parent' => $arrayAccessObject];
        $value = Arr::get($array, 'parent.products.desk.price');
        $this->assertNull($value);

        // Test null ArrayAccess object fields
        $array = new ArrayObject(['foo' => null, 'bar' => new ArrayObject(['baz' => null])]);
        $this->assertNull(Arr::get($array, 'foo', 'default'));
        $this->assertNull(Arr::get($array, 'bar.baz', 'default'));

        // Test null key returns the whole array
        $array = ['foo', 'bar'];
        $this->assertEquals($array, Arr::get($array, null));

        // Test $array not an array
        $this->assertSame('default', Arr::get(null, 'foo', 'default'));
        $this->assertSame('default', Arr::get(false, 'foo', 'default'));

        // Test $array not an array and key is null
        $this->assertSame('default', Arr::get(null, null, 'default'));

        // Test $array is empty and key is null
        $this->assertSame([], Arr::get([], null));
        $this->assertSame([], Arr::get([], null, 'default'));
    }

    public function testHas()
    {
        $array = ['products.desk' => ['price' => 100]];
        $this->assertTrue(Arr::has($array, 'products.desk'));

        $array = ['products' => ['desk' => ['price' => 100]]];
        $this->assertTrue(Arr::has($array, 'products.desk'));
        $this->assertTrue(Arr::has($array, 'products.desk.price'));
        $this->assertFalse(Arr::has($array, 'products.foo'));
        $this->assertFalse(Arr::has($array, 'products.desk.foo'));

        $array = ['foo' => null, 'bar' => ['baz' => null]];
        $this->assertTrue(Arr::has($array, 'foo'));
        $this->assertTrue(Arr::has($array, 'bar.baz'));

        $array = new ArrayObject(['foo' => 10, 'bar' => new ArrayObject(['baz' => 10])]);
        $this->assertTrue(Arr::has($array, 'foo'));
        $this->assertTrue(Arr::has($array, 'bar'));
        $this->assertTrue(Arr::has($array, 'bar.baz'));
        $this->assertFalse(Arr::has($array, 'xxx'));
        $this->assertFalse(Arr::has($array, 'xxx.yyy'));
        $this->assertFalse(Arr::has($array, 'foo.xxx'));
        $this->assertFalse(Arr::has($array, 'bar.xxx'));

        $array = new ArrayObject(['foo' => null, 'bar' => new ArrayObject(['baz' => null])]);
        $this->assertTrue(Arr::has($array, 'foo'));
        $this->assertTrue(Arr::has($array, 'bar.baz'));

        $array = ['foo', 'bar'];
        $this->assertFalse(Arr::has($array, null));

        $this->assertFalse(Arr::has(null, 'foo'));
        $this->assertFalse(Arr::has(false, 'foo'));

        $this->assertFalse(Arr::has(null, null));
        $this->assertFalse(Arr::has([], null));

        $array = ['products' => ['desk' => ['price' => 100]]];
        $this->assertTrue(Arr::has($array, ['products.desk']));
        $this->assertTrue(Arr::has($array, ['products.desk', 'products.desk.price']));
        $this->assertTrue(Arr::has($array, ['products', 'products']));
        $this->assertFalse(Arr::has($array, ['foo']));
        $this->assertFalse(Arr::has($array, []));
        $this->assertFalse(Arr::has($array, ['products.desk', 'products.price']));

        $this->assertFalse(Arr::has([], [null]));
        $this->assertFalse(Arr::has(null, [null]));
    }

    public function testIsAssoc()
    {
        $this->assertTrue(Arr::isAssoc(['a' => 'a', 0 => 'b']));
        $this->assertTrue(Arr::isAssoc([1 => 'a', 0 => 'b']));
        $this->assertTrue(Arr::isAssoc([1 => 'a', 2 => 'b']));
        $this->assertFalse(Arr::isAssoc([0 => 'a', 1 => 'b']));
        $this->assertFalse(Arr::isAssoc(['a', 'b']));
    }

    public function testOnly()
    {
        $array = ['name' => 'Desk', 'price' => 100, 'orders' => 10];
        $array = Arr::only($array, ['name', 'price']);
        $this->assertEquals(['name' => 'Desk', 'price' => 100], $array);
    }

    public function testPluck()
    {
        $array = [
            ['developer' => ['name' => 'Taylor']],
            ['developer' => ['name' => 'Abigail']],
        ];

        $array = Arr::pluck($array, 'developer.name');

        $this->assertEquals(['Taylor', 'Abigail'], $array);
    }

    public function testPluckWithArrayValue()
    {
        $array = [
            ['developer' => ['name' => 'Taylor']],
            ['developer' => ['name' => 'Abigail']],
        ];
        $array = Arr::pluck($array, ['developer', 'name']);
        $this->assertEquals(['Taylor', 'Abigail'], $array);
    }

    public function testPluckWithKeys()
    {
        $array = [
            ['name' => 'Taylor', 'role' => 'developer'],
            ['name' => 'Abigail', 'role' => 'developer'],
        ];

        $test1 = Arr::pluck($array, 'role', 'name');
        $test2 = Arr::pluck($array, null, 'name');

        $this->assertEquals([
            'Taylor' => 'developer',
            'Abigail' => 'developer',
        ], $test1);

        $this->assertEquals([
            'Taylor' => ['name' => 'Taylor', 'role' => 'developer'],
            'Abigail' => ['name' => 'Abigail', 'role' => 'developer'],
        ], $test2);
    }

    public function testPrepend()
    {
        $array = Arr::prepend(['one', 'two', 'three', 'four'], 'zero');
        $this->assertEquals(['zero', 'one', 'two', 'three', 'four'], $array);

        $array = Arr::prepend(['one' => 1, 'two' => 2], 0, 'zero');
        $this->assertEquals(['zero' => 0, 'one' => 1, 'two' => 2], $array);
    }

    public function testPull()
    {
        $array = ['name' => 'Desk', 'price' => 100];
        $name = Arr::pull($array, 'name');
        $this->assertEquals('Desk', $name);
        $this->assertEquals(['price' => 100], $array);

        // Only works on first level keys
        $array = ['joe@example.com' => 'Joe', 'jane@localhost' => 'Jane'];
        $name = Arr::pull($array, 'joe@example.com');
        $this->assertEquals('Joe', $name);
        $this->assertEquals(['jane@localhost' => 'Jane'], $array);

        // Does not work for nested keys
        $array = ['emails' => ['joe@example.com' => 'Joe', 'jane@localhost' => 'Jane']];
        $name = Arr::pull($array, 'emails.joe@example.com');
        $this->assertEquals(null, $name);
        $this->assertEquals(['emails' => ['joe@example.com' => 'Joe', 'jane@localhost' => 'Jane']], $array);
    }

    public function testSet()
    {
        $array = ['products' => ['desk' => ['price' => 100]]];
        Arr::set($array, 'products.desk.price', 200);
        $this->assertEquals(['products' => ['desk' => ['price' => 200]]], $array);
    }

    public function testSort()
    {
        $unsorted = [
            ['name' => 'Desk'],
            ['name' => 'Chair'],
        ];

        $expected = [
            ['name' => 'Chair'],
            ['name' => 'Desk'],
        ];

        // sort with closure
        $sortedWithClosure = array_values(Arr::sort($unsorted, function ($value) {
            return $value['name'];
        }));
        $this->assertEquals($expected, $sortedWithClosure);

        // sort with dot notation
        $sortedWithDotNotation = array_values(Arr::sort($unsorted, 'name'));
        $this->assertEquals($expected, $sortedWithDotNotation);
    }

    public function testSortRecursive()
    {
        $array = [
            'users' => [
                [
                    // should sort associative arrays by keys
                    'name' => 'joe',
                    'mail' => 'joe@example.com',
                    // should sort deeply nested arrays
                    'numbers' => [2, 1, 0],
                ],
                [
                    'name' => 'jane',
                    'age' => 25,
                ],
            ],
            'repositories' => [
                // should use weird `sort()` behavior on arrays of arrays
                ['id' => 1],
                ['id' => 0],
            ],
            // should sort non-associative arrays by value
            20 => [2, 1, 0],
            30 => [
                // should sort non-incrementing numerical keys by keys
                2 => 'a',
                1 => 'b',
                0 => 'c',
            ],
        ];

        $expect = [
            20 => [0, 1, 2],
            30 => [
                0 => 'c',
                1 => 'b',
                2 => 'a',
            ],
            'repositories' => [
                ['id' => 0],
                ['id' => 1],
            ],
            'users' => [
                [
                    'age' => 25,
                    'name' => 'jane',
                ],
                [
                    'mail' => 'joe@example.com',
                    'name' => 'joe',
                    'numbers' => [0, 1, 2],
                ],
            ],
        ];

        $this->assertEquals($expect, Arr::sortRecursive($array));
    }

    public function testWhere()
    {
        $array = [100, '200', 300, '400', 500];

        $array = Arr::where($array, function ($value, $key) {
            return is_string($value);
        });

        $this->assertEquals([1 => 200, 3 => 400], $array);
    }

    public function testWhereKey()
    {
        $array = ['10' => 1, 'foo' => 3, 20 => 2];

        $array = Arr::where($array, function ($value, $key) {
            return is_numeric($key);
        });

        $this->assertEquals(['10' => 1, 20 => 2], $array);
    }

    public function testForget()
    {
        $array = ['products' => ['desk' => ['price' => 100]]];
        Arr::forget($array, null);
        $this->assertEquals(['products' => ['desk' => ['price' => 100]]], $array);

        $array = ['products' => ['desk' => ['price' => 100]]];
        Arr::forget($array, []);
        $this->assertEquals(['products' => ['desk' => ['price' => 100]]], $array);

        $array = ['products' => ['desk' => ['price' => 100]]];
        Arr::forget($array, 'products.desk');
        $this->assertEquals(['products' => []], $array);

        $array = ['products' => ['desk' => ['price' => 100]]];
        Arr::forget($array, 'products.desk.price');
        $this->assertEquals(['products' => ['desk' => []]], $array);

        $array = ['products' => ['desk' => ['price' => 100]]];
        Arr::forget($array, 'products.final.price');
        $this->assertEquals(['products' => ['desk' => ['price' => 100]]], $array);

        $array = ['shop' => ['cart' => [150 => 0]]];
        Arr::forget($array, 'shop.final.cart');
        $this->assertEquals(['shop' => ['cart' => [150 => 0]]], $array);

        $array = ['products' => ['desk' => ['price' => ['original' => 50, 'taxes' => 60]]]];
        Arr::forget($array, 'products.desk.price.taxes');
        $this->assertEquals(['products' => ['desk' => ['price' => ['original' => 50]]]], $array);

        $array = ['products' => ['desk' => ['price' => ['original' => 50, 'taxes' => 60]]]];
        Arr::forget($array, 'products.desk.final.taxes');
        $this->assertEquals(['products' => ['desk' => ['price' => ['original' => 50, 'taxes' => 60]]]], $array);

        $array = ['products' => ['desk' => ['price' => 50], null => 'something']];
        Arr::forget($array, ['products.amount.all', 'products.desk.price']);
        $this->assertEquals(['products' => ['desk' => [], null => 'something']], $array);

        // Only works on first level keys
        $array = ['joe@example.com' => 'Joe', 'jane@example.com' => 'Jane'];
        Arr::forget($array, 'joe@example.com');
        $this->assertEquals(['jane@example.com' => 'Jane'], $array);

        // Does not work for nested keys
        $array = ['emails' => ['joe@example.com' => ['name' => 'Joe'], 'jane@localhost' => ['name' => 'Jane']]];
        Arr::forget($array, ['emails.joe@example.com', 'emails.jane@localhost']);
        $this->assertEquals(['emails' => ['joe@example.com' => ['name' => 'Joe']]], $array);
    }

    public function testWrap()
    {
        $string = 'a';
        $array = ['a'];
        $object = new stdClass;
        $object->value = 'a';
        $this->assertEquals(['a'], Arr::wrap($string));
        $this->assertEquals($array, Arr::wrap($array));
        $this->assertEquals([$object], Arr::wrap($object));
    }
}
