<?php

namespace Illuminate\Tests\Support;

use ArrayObject;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;

class SupportArrTest extends TestCase
{
    public function testAccessible(): void
    {
        $this->assertTrue(Arr::accessible([]));
        $this->assertTrue(Arr::accessible([1, 2]));
        $this->assertTrue(Arr::accessible(['a' => 1, 'b' => 2]));
        $this->assertTrue(Arr::accessible(new Collection));

        $this->assertFalse(Arr::accessible(null));
        $this->assertFalse(Arr::accessible('abc'));
        $this->assertFalse(Arr::accessible(new stdClass));
        $this->assertFalse(Arr::accessible((object) ['a' => 1, 'b' => 2]));
        $this->assertFalse(Arr::accessible(123));
        $this->assertFalse(Arr::accessible(12.34));
        $this->assertFalse(Arr::accessible(true));
        $this->assertFalse(Arr::accessible(new \DateTime));
        $this->assertFalse(Arr::accessible(static fn () => null));
    }

    public function testAdd()
    {
        $array = Arr::add(['name' => 'Desk'], 'price', 100);
        $this->assertEquals(['name' => 'Desk', 'price' => 100], $array);

        $this->assertEquals(['surname' => 'Mövsümov'], Arr::add([], 'surname', 'Mövsümov'));
        $this->assertEquals(['developer' => ['name' => 'Ferid']], Arr::add([], 'developer.name', 'Ferid'));
        $this->assertEquals([1 => 'hAz'], Arr::add([], 1, 'hAz'));
        $this->assertEquals([1 => [1 => 'hAz']], Arr::add([], 1.1, 'hAz'));
    }

    public function testCollapse()
    {
        // Normal case: a two-dimensional array with different elements
        $data = [['foo', 'bar'], ['baz']];
        $this->assertEquals(['foo', 'bar', 'baz'], Arr::collapse($data));

        // Case including numeric and string elements
        $array = [[1], [2], [3], ['foo', 'bar'], collect(['baz', 'boom'])];
        $this->assertEquals([1, 2, 3, 'foo', 'bar', 'baz', 'boom'], Arr::collapse($array));

        // Case with empty two-dimensional arrays
        $emptyArray = [[], [], []];
        $this->assertEquals([], Arr::collapse($emptyArray));

        // Case with both empty arrays and arrays with elements
        $mixedArray = [[], [1, 2], [], ['foo', 'bar']];
        $this->assertEquals([1, 2, 'foo', 'bar'], Arr::collapse($mixedArray));

        // Case including collections and arrays
        $collection = collect(['baz', 'boom']);
        $mixedArray = [[1], [2], [3], ['foo', 'bar'], $collection];
        $this->assertEquals([1, 2, 3, 'foo', 'bar', 'baz', 'boom'], Arr::collapse($mixedArray));
    }

    public function testCrossJoin()
    {
        // Single dimension
        $this->assertSame(
            [[1, 'a'], [1, 'b'], [1, 'c']],
            Arr::crossJoin([1], ['a', 'b', 'c'])
        );

        // Square matrix
        $this->assertSame(
            [[1, 'a'], [1, 'b'], [2, 'a'], [2, 'b']],
            Arr::crossJoin([1, 2], ['a', 'b'])
        );

        // Rectangular matrix
        $this->assertSame(
            [[1, 'a'], [1, 'b'], [1, 'c'], [2, 'a'], [2, 'b'], [2, 'c']],
            Arr::crossJoin([1, 2], ['a', 'b', 'c'])
        );

        // 3D matrix
        $this->assertSame(
            [
                [1, 'a', 'I'], [1, 'a', 'II'], [1, 'a', 'III'],
                [1, 'b', 'I'], [1, 'b', 'II'], [1, 'b', 'III'],
                [2, 'a', 'I'], [2, 'a', 'II'], [2, 'a', 'III'],
                [2, 'b', 'I'], [2, 'b', 'II'], [2, 'b', 'III'],
            ],
            Arr::crossJoin([1, 2], ['a', 'b'], ['I', 'II', 'III'])
        );

        // With 1 empty dimension
        $this->assertEmpty(Arr::crossJoin([], ['a', 'b'], ['I', 'II', 'III']));
        $this->assertEmpty(Arr::crossJoin([1, 2], [], ['I', 'II', 'III']));
        $this->assertEmpty(Arr::crossJoin([1, 2], ['a', 'b'], []));

        // With empty arrays
        $this->assertEmpty(Arr::crossJoin([], [], []));
        $this->assertEmpty(Arr::crossJoin([], []));
        $this->assertEmpty(Arr::crossJoin([]));

        // Not really a proper usage, still, test for preserving BC
        $this->assertSame([[]], Arr::crossJoin());
    }

    public function testDivide(): void
    {
        // Test dividing an empty array
        [$keys, $values] = Arr::divide([]);
        $this->assertEquals([], $keys);
        $this->assertEquals([], $values);

        // Test dividing an array with a single key-value pair
        [$keys, $values] = Arr::divide(['name' => 'Desk']);
        $this->assertEquals(['name'], $keys);
        $this->assertEquals(['Desk'], $values);

        // Test dividing an array with multiple key-value pairs
        [$keys, $values] = Arr::divide(['name' => 'Desk', 'price' => 100, 'available' => true]);
        $this->assertEquals(['name', 'price', 'available'], $keys);
        $this->assertEquals(['Desk', 100, true], $values);

        // Test dividing an array with numeric keys
        [$keys, $values] = Arr::divide([0 => 'first', 1 => 'second']);
        $this->assertEquals([0, 1], $keys);
        $this->assertEquals(['first', 'second'], $values);

        // Test dividing an array with null key
        [$keys, $values] = Arr::divide([null => 'Null', 1 => 'one']);
        $this->assertEquals([null, 1], $keys);
        $this->assertEquals(['Null', 'one'], $values);

        // Test dividing an array where the keys are arrays
        [$keys, $values] = Arr::divide([['one' => 1, 2 => 'second'], 1 => 'one']);
        $this->assertEquals([0, 1], $keys);
        $this->assertEquals([['one' => 1, 2 => 'second'], 'one'], $values);

        // Test dividing an array where the values are arrays
        [$keys, $values] = Arr::divide([null => ['one' => 1, 2 => 'second'], 1 => 'one']);
        $this->assertEquals([null, 1], $keys);
        $this->assertEquals([['one' => 1, 2 => 'second'], 'one'], $values);
    }

    public function testDot()
    {
        $array = Arr::dot(['foo' => ['bar' => 'baz']]);
        $this->assertSame(['foo.bar' => 'baz'], $array);

        $array = Arr::dot([10 => 100]);
        $this->assertSame([10 => 100], $array);

        $array = Arr::dot(['foo' => [10 => 100]]);
        $this->assertSame(['foo.10' => 100], $array);

        $array = Arr::dot([]);
        $this->assertSame([], $array);

        $array = Arr::dot(['foo' => []]);
        $this->assertSame(['foo' => []], $array);

        $array = Arr::dot(['foo' => ['bar' => []]]);
        $this->assertSame(['foo.bar' => []], $array);

        $array = Arr::dot(['name' => 'taylor', 'languages' => ['php' => true]]);
        $this->assertSame(['name' => 'taylor', 'languages.php' => true], $array);

        $array = Arr::dot(['user' => ['name' => 'Taylor', 'age' => 25, 'languages' => ['PHP', 'C#']]]);
        $this->assertSame([
            'user.name' => 'Taylor',
            'user.age' => 25,
            'user.languages.0' => 'PHP',
            'user.languages.1' => 'C#',
        ], $array);

        $array = Arr::dot(['foo', 'foo' => ['bar' => 'baz', 'baz' => ['a' => 'b']]]);
        $this->assertSame([
            'foo',
            'foo.bar' => 'baz',
            'foo.baz.a' => 'b',
        ], $array);

        $array = Arr::dot(['foo' => 'bar', 'empty_array' => [], 'user' => ['name' => 'Taylor'], 'key' => 'value']);
        $this->assertSame([
            'foo' => 'bar',
            'empty_array' => [],
            'user.name' => 'Taylor',
            'key' => 'value',
        ], $array);
    }

    public function testUndot()
    {
        $array = Arr::undot([
            'user.name' => 'Taylor',
            'user.age' => 25,
            'user.languages.0' => 'PHP',
            'user.languages.1' => 'C#',
        ]);
        $this->assertEquals(['user' => ['name' => 'Taylor', 'age' => 25, 'languages' => ['PHP', 'C#']]], $array);

        $array = Arr::undot([
            'pagination.previous' => '<<',
            'pagination.next' => '>>',
        ]);
        $this->assertEquals(['pagination' => ['previous' => '<<', 'next' => '>>']], $array);

        $array = Arr::undot([
            'foo',
            'foo.bar' => 'baz',
            'foo.baz' => ['a' => 'b'],
        ]);
        $this->assertEquals(['foo', 'foo' => ['bar' => 'baz', 'baz' => ['a' => 'b']]], $array);
    }

    public function testExcept()
    {
        $array = ['name' => 'taylor', 'age' => 26];
        $this->assertEquals(['age' => 26], Arr::except($array, ['name']));
        $this->assertEquals(['age' => 26], Arr::except($array, 'name'));

        $array = ['name' => 'taylor', 'framework' => ['language' => 'PHP', 'name' => 'Laravel']];
        $this->assertEquals(['name' => 'taylor'], Arr::except($array, 'framework'));
        $this->assertEquals(['name' => 'taylor', 'framework' => ['name' => 'Laravel']], Arr::except($array, 'framework.language'));
        $this->assertEquals(['framework' => ['language' => 'PHP']], Arr::except($array, ['name', 'framework.name']));

        $array = [1 => 'hAz', 2 => [5 => 'foo', 12 => 'baz']];
        $this->assertEquals([1 => 'hAz'], Arr::except($array, 2));
        $this->assertEquals([1 => 'hAz', 2 => [12 => 'baz']], Arr::except($array, 2.5));
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

    public function testWhereNotNull(): void
    {
        $array = array_values(Arr::whereNotNull([null, 0, false, '', null, []]));
        $this->assertEquals([0, false, '', []], $array);

        $array = array_values(Arr::whereNotNull([1, 2, 3]));
        $this->assertEquals([1, 2, 3], $array);

        $array = array_values(Arr::whereNotNull([null, null, null]));
        $this->assertEquals([], $array);

        $array = array_values(Arr::whereNotNull(['a', null, 'b', null, 'c']));
        $this->assertEquals(['a', 'b', 'c'], $array);

        $array = array_values(Arr::whereNotNull([null, 1, 'string', 0.0, false, [], new stdClass(), fn () => null]));
        $this->assertEquals([1, 'string', 0.0, false, [], new stdClass(), fn () => null], $array);
    }

    public function testFirst()
    {
        $array = [100, 200, 300];

        // Callback is null and array is empty
        $this->assertNull(Arr::first([], null));
        $this->assertSame('foo', Arr::first([], null, 'foo'));
        $this->assertSame('bar', Arr::first([], null, function () {
            return 'bar';
        }));

        // Callback is null and array is not empty
        $this->assertEquals(100, Arr::first($array));

        // Callback is not null and array is not empty
        $value = Arr::first($array, function ($value) {
            return $value >= 150;
        });
        $this->assertEquals(200, $value);

        // Callback is not null, array is not empty but no satisfied item
        $value2 = Arr::first($array, function ($value) {
            return $value > 300;
        });
        $value3 = Arr::first($array, function ($value) {
            return $value > 300;
        }, 'bar');
        $value4 = Arr::first($array, function ($value) {
            return $value > 300;
        }, function () {
            return 'baz';
        });
        $value5 = Arr::first($array, function ($value, $key) {
            return $key < 2;
        });
        $this->assertNull($value2);
        $this->assertSame('bar', $value3);
        $this->assertSame('baz', $value4);
        $this->assertEquals(100, $value5);

        $cursor = (function () {
            while (false) {
                yield 1;
            }
        })();
        $this->assertNull(Arr::first($cursor));
    }

    public function testJoin()
    {
        $this->assertSame('a, b, c', Arr::join(['a', 'b', 'c'], ', '));

        $this->assertSame('a, b and c', Arr::join(['a', 'b', 'c'], ', ', ' and '));

        $this->assertSame('a and b', Arr::join(['a', 'b'], ', ', ' and '));

        $this->assertSame('a', Arr::join(['a'], ', ', ' and '));

        $this->assertSame('', Arr::join([], ', ', ' and '));
    }

    public function testLast()
    {
        $array = [100, 200, 300];

        // Callback is null and array is empty
        $this->assertNull(Arr::last([], null));
        $this->assertSame('foo', Arr::last([], null, 'foo'));
        $this->assertSame('bar', Arr::last([], null, function () {
            return 'bar';
        }));

        // Callback is null and array is not empty
        $this->assertEquals(300, Arr::last($array));

        // Callback is not null and array is not empty
        $value = Arr::last($array, function ($value) {
            return $value < 250;
        });
        $this->assertEquals(200, $value);

        // Callback is not null, array is not empty but no satisfied item
        $value2 = Arr::last($array, function ($value) {
            return $value > 300;
        });
        $value3 = Arr::last($array, function ($value) {
            return $value > 300;
        }, 'bar');
        $value4 = Arr::last($array, function ($value) {
            return $value > 300;
        }, function () {
            return 'baz';
        });
        $value5 = Arr::last($array, function ($value, $key) {
            return $key < 2;
        });
        $this->assertNull($value2);
        $this->assertSame('bar', $value3);
        $this->assertSame('baz', $value4);
        $this->assertEquals(200, $value5);
    }

    public function testFlatten()
    {
        // Flat arrays are unaffected
        $array = ['#foo', '#bar', '#baz'];
        $this->assertEquals(['#foo', '#bar', '#baz'], Arr::flatten($array));

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
        $this->assertEmpty(Arr::get([], null));
        $this->assertEmpty(Arr::get([], null, 'default'));

        // Test numeric keys
        $array = [
            'products' => [
                ['name' => 'desk'],
                ['name' => 'chair'],
            ],
        ];
        $this->assertSame('desk', Arr::get($array, 'products.0.name'));
        $this->assertSame('chair', Arr::get($array, 'products.1.name'));

        // Test return default value for non-existing key.
        $array = ['names' => ['developer' => 'taylor']];
        $this->assertSame('dayle', Arr::get($array, 'names.otherDeveloper', 'dayle'));
        $this->assertSame('dayle', Arr::get($array, 'names.otherDeveloper', function () {
            return 'dayle';
        }));
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

        $array = [
            'products' => [
                ['name' => 'desk'],
            ],
        ];
        $this->assertTrue(Arr::has($array, 'products.0.name'));
        $this->assertFalse(Arr::has($array, 'products.0.price'));

        $this->assertFalse(Arr::has([], [null]));
        $this->assertFalse(Arr::has(null, [null]));

        $this->assertTrue(Arr::has(['' => 'some'], ''));
        $this->assertTrue(Arr::has(['' => 'some'], ['']));
        $this->assertFalse(Arr::has([''], ''));
        $this->assertFalse(Arr::has([], ''));
        $this->assertFalse(Arr::has([], ['']));
    }

    public function testHasAnyMethod()
    {
        $array = ['name' => 'Taylor', 'age' => '', 'city' => null];
        $this->assertTrue(Arr::hasAny($array, 'name'));
        $this->assertTrue(Arr::hasAny($array, 'age'));
        $this->assertTrue(Arr::hasAny($array, 'city'));
        $this->assertFalse(Arr::hasAny($array, 'foo'));
        $this->assertTrue(Arr::hasAny($array, 'name', 'email'));
        $this->assertTrue(Arr::hasAny($array, ['name', 'email']));

        $array = ['name' => 'Taylor', 'email' => 'foo'];
        $this->assertTrue(Arr::hasAny($array, 'name', 'email'));
        $this->assertFalse(Arr::hasAny($array, 'surname', 'password'));
        $this->assertFalse(Arr::hasAny($array, ['surname', 'password']));

        $array = ['foo' => ['bar' => null, 'baz' => '']];
        $this->assertTrue(Arr::hasAny($array, 'foo.bar'));
        $this->assertTrue(Arr::hasAny($array, 'foo.baz'));
        $this->assertFalse(Arr::hasAny($array, 'foo.bax'));
        $this->assertTrue(Arr::hasAny($array, ['foo.bax', 'foo.baz']));
    }

    public function testIsAssoc()
    {
        $this->assertTrue(Arr::isAssoc(['a' => 'a', 0 => 'b']));
        $this->assertTrue(Arr::isAssoc([1 => 'a', 0 => 'b']));
        $this->assertTrue(Arr::isAssoc([1 => 'a', 2 => 'b']));
        $this->assertFalse(Arr::isAssoc([0 => 'a', 1 => 'b']));
        $this->assertFalse(Arr::isAssoc(['a', 'b']));

        $this->assertFalse(Arr::isAssoc([]));
        $this->assertFalse(Arr::isAssoc([1, 2, 3]));
        $this->assertFalse(Arr::isAssoc(['foo', 2, 3]));
        $this->assertFalse(Arr::isAssoc([0 => 'foo', 'bar']));

        $this->assertTrue(Arr::isAssoc([1 => 'foo', 'bar']));
        $this->assertTrue(Arr::isAssoc([0 => 'foo', 'bar' => 'baz']));
        $this->assertTrue(Arr::isAssoc([0 => 'foo', 2 => 'bar']));
        $this->assertTrue(Arr::isAssoc(['foo' => 'bar', 'baz' => 'qux']));
    }

    public function testIsList()
    {
        $this->assertTrue(Arr::isList([]));
        $this->assertTrue(Arr::isList([1, 2, 3]));
        $this->assertTrue(Arr::isList(['foo', 2, 3]));
        $this->assertTrue(Arr::isList(['foo', 'bar']));
        $this->assertTrue(Arr::isList([0 => 'foo', 'bar']));
        $this->assertTrue(Arr::isList([0 => 'foo', 1 => 'bar']));

        $this->assertFalse(Arr::isList([1 => 'foo', 'bar']));
        $this->assertFalse(Arr::isList([1 => 'foo', 0 => 'bar']));
        $this->assertFalse(Arr::isList([0 => 'foo', 'bar' => 'baz']));
        $this->assertFalse(Arr::isList([0 => 'foo', 2 => 'bar']));
        $this->assertFalse(Arr::isList(['foo' => 'bar', 'baz' => 'qux']));
    }

    public function testOnly()
    {
        $array = ['name' => 'Desk', 'price' => 100, 'orders' => 10];
        $array = Arr::only($array, ['name', 'price']);
        $this->assertEquals(['name' => 'Desk', 'price' => 100], $array);
        $this->assertEmpty(Arr::only($array, ['nonExistingKey']));
    }

    public function testPluck()
    {
        $data = [
            'post-1' => [
                'comments' => [
                    'tags' => [
                        '#foo', '#bar',
                    ],
                ],
            ],
            'post-2' => [
                'comments' => [
                    'tags' => [
                        '#baz',
                    ],
                ],
            ],
        ];

        $this->assertEquals([
            0 => [
                'tags' => [
                    '#foo', '#bar',
                ],
            ],
            1 => [
                'tags' => [
                    '#baz',
                ],
            ],
        ], Arr::pluck($data, 'comments'));

        $this->assertEquals([['#foo', '#bar'], ['#baz']], Arr::pluck($data, 'comments.tags'));
        $this->assertEquals([null, null], Arr::pluck($data, 'foo'));
        $this->assertEquals([null, null], Arr::pluck($data, 'foo.bar'));

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

    public function testPluckWithCarbonKeys()
    {
        $array = [
            ['start' => new Carbon('2017-07-25 00:00:00'), 'end' => new Carbon('2017-07-30 00:00:00')],
        ];
        $array = Arr::pluck($array, 'end', 'start');
        $this->assertEquals(['2017-07-25 00:00:00' => '2017-07-30 00:00:00'], $array);
    }

    public function testArrayPluckWithArrayAndObjectValues()
    {
        $array = [(object) ['name' => 'taylor', 'email' => 'foo'], ['name' => 'dayle', 'email' => 'bar']];
        $this->assertEquals(['taylor', 'dayle'], Arr::pluck($array, 'name'));
        $this->assertEquals(['taylor' => 'foo', 'dayle' => 'bar'], Arr::pluck($array, 'email', 'name'));
    }

    public function testArrayPluckWithNestedKeys()
    {
        $array = [['user' => ['taylor', 'otwell']], ['user' => ['dayle', 'rees']]];
        $this->assertEquals(['taylor', 'dayle'], Arr::pluck($array, 'user.0'));
        $this->assertEquals(['taylor', 'dayle'], Arr::pluck($array, ['user', 0]));
        $this->assertEquals(['taylor' => 'otwell', 'dayle' => 'rees'], Arr::pluck($array, 'user.1', 'user.0'));
        $this->assertEquals(['taylor' => 'otwell', 'dayle' => 'rees'], Arr::pluck($array, ['user', 1], ['user', 0]));
    }

    public function testArrayPluckWithNestedArrays()
    {
        $array = [
            [
                'account' => 'a',
                'users' => [
                    ['first' => 'taylor', 'last' => 'otwell', 'email' => 'taylorotwell@gmail.com'],
                ],
            ],
            [
                'account' => 'b',
                'users' => [
                    ['first' => 'abigail', 'last' => 'otwell'],
                    ['first' => 'dayle', 'last' => 'rees'],
                ],
            ],
        ];

        $this->assertEquals([['taylor'], ['abigail', 'dayle']], Arr::pluck($array, 'users.*.first'));
        $this->assertEquals(['a' => ['taylor'], 'b' => ['abigail', 'dayle']], Arr::pluck($array, 'users.*.first', 'account'));
        $this->assertEquals([['taylorotwell@gmail.com'], [null, null]], Arr::pluck($array, 'users.*.email'));
    }

    public function testMap()
    {
        $data = ['first' => 'taylor', 'last' => 'otwell'];
        $mapped = Arr::map($data, function ($value, $key) {
            return $key.'-'.strrev($value);
        });
        $this->assertEquals(['first' => 'first-rolyat', 'last' => 'last-llewto'], $mapped);
        $this->assertEquals(['first' => 'taylor', 'last' => 'otwell'], $data);
    }

    public function testMapWithEmptyArray()
    {
        $mapped = Arr::map([], static function ($value, $key) {
            return $key.'-'.$value;
        });
        $this->assertEquals([], $mapped);
    }

    public function testMapNullValues()
    {
        $data = ['first' => 'taylor', 'last' => null];
        $mapped = Arr::map($data, static function ($value, $key) {
            return $key.'-'.$value;
        });
        $this->assertEquals(['first' => 'first-taylor', 'last' => 'last-'], $mapped);
    }

    public function testMapWithKeys()
    {
        $data = [
            ['name' => 'Blastoise', 'type' => 'Water', 'idx' => 9],
            ['name' => 'Charmander', 'type' => 'Fire', 'idx' => 4],
            ['name' => 'Dragonair', 'type' => 'Dragon', 'idx' => 148],
        ];

        $data = Arr::mapWithKeys($data, function ($pokemon) {
            return [$pokemon['name'] => $pokemon['type']];
        });

        $this->assertEquals(
            ['Blastoise' => 'Water', 'Charmander' => 'Fire', 'Dragonair' => 'Dragon'],
            $data
        );
    }

    public function testMapByReference()
    {
        $data = ['first' => 'taylor', 'last' => 'otwell'];
        $mapped = Arr::map($data, 'strrev');

        $this->assertEquals(['first' => 'rolyat', 'last' => 'llewto'], $mapped);
        $this->assertEquals(['first' => 'taylor', 'last' => 'otwell'], $data);
    }

    public function testMapSpread()
    {
        $c = [[1, 'a'], [2, 'b']];

        $result = Arr::mapSpread($c, function ($number, $character) {
            return "{$number}-{$character}";
        });
        $this->assertEquals(['1-a', '2-b'], $result);

        $result = Arr::mapSpread($c, function ($number, $character, $key) {
            return "{$number}-{$character}-{$key}";
        });
        $this->assertEquals(['1-a-0', '2-b-1'], $result);
    }

    public function testPrepend()
    {
        $array = Arr::prepend(['one', 'two', 'three', 'four'], 'zero');
        $this->assertEquals(['zero', 'one', 'two', 'three', 'four'], $array);

        $array = Arr::prepend(['one' => 1, 'two' => 2], 0, 'zero');
        $this->assertEquals(['zero' => 0, 'one' => 1, 'two' => 2], $array);

        $array = Arr::prepend(['one' => 1, 'two' => 2], 0, null);
        $this->assertEquals([null => 0, 'one' => 1, 'two' => 2], $array);

        $array = Arr::prepend(['one', 'two'], null, '');
        $this->assertEquals(['' => null, 'one', 'two'], $array);

        $array = Arr::prepend([], 'zero');
        $this->assertEquals(['zero'], $array);

        $array = Arr::prepend([''], 'zero');
        $this->assertEquals(['zero', ''], $array);

        $array = Arr::prepend(['one', 'two'], ['zero']);
        $this->assertEquals([['zero'], 'one', 'two'], $array);

        $array = Arr::prepend(['one', 'two'], ['zero'], 'key');
        $this->assertEquals(['key' => ['zero'], 'one', 'two'], $array);
    }

    public function testPull()
    {
        $array = ['name' => 'Desk', 'price' => 100];
        $name = Arr::pull($array, 'name');
        $this->assertSame('Desk', $name);
        $this->assertSame(['price' => 100], $array);

        // Only works on first level keys
        $array = ['joe@example.com' => 'Joe', 'jane@localhost' => 'Jane'];
        $name = Arr::pull($array, 'joe@example.com');
        $this->assertSame('Joe', $name);
        $this->assertSame(['jane@localhost' => 'Jane'], $array);

        // Does not work for nested keys
        $array = ['emails' => ['joe@example.com' => 'Joe', 'jane@localhost' => 'Jane']];
        $name = Arr::pull($array, 'emails.joe@example.com');
        $this->assertNull($name);
        $this->assertSame(['emails' => ['joe@example.com' => 'Joe', 'jane@localhost' => 'Jane']], $array);

        // Works with int keys
        $array = ['First', 'Second'];
        $first = Arr::pull($array, 0);
        $this->assertSame('First', $first);
        $this->assertSame([1 => 'Second'], $array);
    }

    public function testQuery()
    {
        $this->assertSame('', Arr::query([]));
        $this->assertSame('foo=bar', Arr::query(['foo' => 'bar']));
        $this->assertSame('foo=bar&bar=baz', Arr::query(['foo' => 'bar', 'bar' => 'baz']));
        $this->assertSame('foo=bar&bar=1', Arr::query(['foo' => 'bar', 'bar' => true]));
        $this->assertSame('foo=bar', Arr::query(['foo' => 'bar', 'bar' => null]));
        $this->assertSame('foo=bar&bar=', Arr::query(['foo' => 'bar', 'bar' => '']));
    }

    public function testRandom()
    {
        $random = Arr::random(['foo', 'bar', 'baz']);
        $this->assertContains($random, ['foo', 'bar', 'baz']);

        $random = Arr::random(['foo', 'bar', 'baz'], 0);
        $this->assertIsArray($random);
        $this->assertCount(0, $random);

        $random = Arr::random(['foo', 'bar', 'baz'], 1);
        $this->assertIsArray($random);
        $this->assertCount(1, $random);
        $this->assertContains($random[0], ['foo', 'bar', 'baz']);

        $random = Arr::random(['foo', 'bar', 'baz'], 2);
        $this->assertIsArray($random);
        $this->assertCount(2, $random);
        $this->assertContains($random[0], ['foo', 'bar', 'baz']);
        $this->assertContains($random[1], ['foo', 'bar', 'baz']);

        $random = Arr::random(['foo', 'bar', 'baz'], '0');
        $this->assertIsArray($random);
        $this->assertCount(0, $random);

        $random = Arr::random(['foo', 'bar', 'baz'], '1');
        $this->assertIsArray($random);
        $this->assertCount(1, $random);
        $this->assertContains($random[0], ['foo', 'bar', 'baz']);

        $random = Arr::random(['foo', 'bar', 'baz'], '2');
        $this->assertIsArray($random);
        $this->assertCount(2, $random);
        $this->assertContains($random[0], ['foo', 'bar', 'baz']);
        $this->assertContains($random[1], ['foo', 'bar', 'baz']);

        // preserve keys
        $random = Arr::random(['one' => 'foo', 'two' => 'bar', 'three' => 'baz'], 2, true);
        $this->assertIsArray($random);
        $this->assertCount(2, $random);
        $this->assertCount(2, array_intersect_assoc(['one' => 'foo', 'two' => 'bar', 'three' => 'baz'], $random));
    }

    public function testRandomNotIncrementingKeys()
    {
        $random = Arr::random(['foo' => 'foo', 'bar' => 'bar', 'baz' => 'baz']);
        $this->assertContains($random, ['foo', 'bar', 'baz']);
    }

    public function testRandomOnEmptyArray()
    {
        $random = Arr::random([], 0);
        $this->assertIsArray($random);
        $this->assertCount(0, $random);

        $random = Arr::random([], '0');
        $this->assertIsArray($random);
        $this->assertCount(0, $random);
    }

    public function testRandomThrowsAnErrorWhenRequestingMoreItemsThanAreAvailable()
    {
        $exceptions = 0;

        try {
            Arr::random([]);
        } catch (InvalidArgumentException) {
            $exceptions++;
        }

        try {
            Arr::random([], 1);
        } catch (InvalidArgumentException) {
            $exceptions++;
        }

        try {
            Arr::random([], 2);
        } catch (InvalidArgumentException) {
            $exceptions++;
        }

        $this->assertSame(3, $exceptions);
    }

    public function testSet()
    {
        $array = ['products' => ['desk' => ['price' => 100]]];
        Arr::set($array, 'products.desk.price', 200);
        $this->assertEquals(['products' => ['desk' => ['price' => 200]]], $array);

        // No key is given
        $array = ['products' => ['desk' => ['price' => 100]]];
        Arr::set($array, null, ['price' => 300]);
        $this->assertSame(['price' => 300], $array);

        // The key doesn't exist at the depth
        $array = ['products' => 'desk'];
        Arr::set($array, 'products.desk.price', 200);
        $this->assertSame(['products' => ['desk' => ['price' => 200]]], $array);

        // No corresponding key exists
        $array = ['products'];
        Arr::set($array, 'products.desk.price', 200);
        $this->assertSame(['products', 'products' => ['desk' => ['price' => 200]]], $array);

        $array = ['products' => ['desk' => ['price' => 100]]];
        Arr::set($array, 'table', 500);
        $this->assertSame(['products' => ['desk' => ['price' => 100]], 'table' => 500], $array);

        $array = ['products' => ['desk' => ['price' => 100]]];
        Arr::set($array, 'table.price', 350);
        $this->assertSame(['products' => ['desk' => ['price' => 100]], 'table' => ['price' => 350]], $array);

        $array = [];
        Arr::set($array, 'products.desk.price', 200);
        $this->assertSame(['products' => ['desk' => ['price' => 200]]], $array);

        // Override
        $array = ['products' => 'table'];
        Arr::set($array, 'products.desk.price', 300);
        $this->assertSame(['products' => ['desk' => ['price' => 300]]], $array);

        $array = [1 => 'test'];
        $this->assertEquals([1 => 'hAz'], Arr::set($array, 1, 'hAz'));
    }

    public function testShuffleProducesDifferentShuffles()
    {
        $input = range('a', 'z');

        $this->assertFalse(
            Arr::shuffle($input) === Arr::shuffle($input) && Arr::shuffle($input) === Arr::shuffle($input),
            "The shuffles produced the same output each time, which shouldn't happen."
        );
    }

    public function testShuffleActuallyShuffles()
    {
        $input = range('a', 'z');

        $this->assertFalse(
            Arr::shuffle($input) === $input && Arr::shuffle($input) === $input,
            "The shuffles were unshuffled each time, which shouldn't happen."
        );
    }

    public function testShuffleKeepsSameValues()
    {
        $input = range('a', 'z');
        $shuffled = Arr::shuffle($input);
        sort($shuffled);

        $this->assertEquals($input, $shuffled);
    }

    public function testEmptyShuffle()
    {
        $this->assertEquals([], Arr::shuffle([]));
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

        $sorted = array_values(Arr::sort($unsorted));
        $this->assertEquals($expected, $sorted);

        // sort with closure
        $sortedWithClosure = array_values(Arr::sort($unsorted, function ($value) {
            return $value['name'];
        }));
        $this->assertEquals($expected, $sortedWithClosure);

        // sort with dot notation
        $sortedWithDotNotation = array_values(Arr::sort($unsorted, 'name'));
        $this->assertEquals($expected, $sortedWithDotNotation);
    }

    public function testSortDesc()
    {
        $unsorted = [
            ['name' => 'Chair'],
            ['name' => 'Desk'],
        ];

        $expected = [
            ['name' => 'Desk'],
            ['name' => 'Chair'],
        ];

        $sorted = array_values(Arr::sortDesc($unsorted));
        $this->assertEquals($expected, $sorted);

        // sort with closure
        $sortedWithClosure = array_values(Arr::sortDesc($unsorted, function ($value) {
            return $value['name'];
        }));
        $this->assertEquals($expected, $sortedWithClosure);

        // sort with dot notation
        $sortedWithDotNotation = array_values(Arr::sortDesc($unsorted, 'name'));
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

    public function testSortRecursiveDesc()
    {
        $array = [
            'empty' => [],
            'nested' => [
                'level1' => [
                    'level2' => [
                        'level3' => [2, 3, 1],
                    ],
                    'values' => [4, 5, 6],
                ],
            ],
            'mixed' => [
                'a' => 1,
                2 => 'b',
                'c' => 3,
                1 => 'd',
            ],
            'numbered_index' => [
                1 => 'e',
                3 => 'c',
                4 => 'b',
                5 => 'a',
                2 => 'd',
            ],
        ];

        $expect = [
            'empty' => [],
            'mixed' => [
                'c' => 3,
                'a' => 1,
                2 => 'b',
                1 => 'd',
            ],
            'nested' => [
                'level1' => [
                    'values' => [6, 5, 4],
                    'level2' => [
                        'level3' => [3, 2, 1],
                    ],
                ],
            ],
            'numbered_index' => [
                5 => 'a',
                4 => 'b',
                3 => 'c',
                2 => 'd',
                1 => 'e',
            ],
        ];

        $this->assertEquals($expect, Arr::sortRecursiveDesc($array));
    }

    public function testToCssClasses()
    {
        $classes = Arr::toCssClasses([
            'font-bold',
            'mt-4',
        ]);

        $this->assertSame('font-bold mt-4', $classes);

        $classes = Arr::toCssClasses([
            'font-bold',
            'mt-4',
            'ml-2' => true,
            'mr-2' => false,
        ]);

        $this->assertSame('font-bold mt-4 ml-2', $classes);
    }

    public function testToCssStyles()
    {
        $styles = Arr::toCssStyles([
            'font-weight: bold',
            'margin-top: 4px;',
        ]);

        $this->assertSame('font-weight: bold; margin-top: 4px;', $styles);

        $styles = Arr::toCssStyles([
            'font-weight: bold;',
            'margin-top: 4px',
            'margin-left: 2px;' => true,
            'margin-right: 2px' => false,
        ]);

        $this->assertSame('font-weight: bold; margin-top: 4px; margin-left: 2px;', $styles);
    }

    public function testWhere()
    {
        $array = [100, '200', 300, '400', 500];

        $array = Arr::where($array, function ($value, $key) {
            return is_string($value);
        });

        $this->assertEquals([1 => '200', 3 => '400'], $array);
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

        $array = ['name' => 'hAz', '1' => 'test', 2 => 'bAz'];
        Arr::forget($array, 1);
        $this->assertEquals(['name' => 'hAz', 2 => 'bAz'], $array);

        $array = [2 => [1 => 'products', 3 => 'users']];
        Arr::forget($array, 2.3);
        $this->assertEquals([2 => [1 => 'products']], $array);
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
        $this->assertEquals([], Arr::wrap(null));
        $this->assertEquals([null], Arr::wrap([null]));
        $this->assertEquals([null, null], Arr::wrap([null, null]));
        $this->assertEquals([''], Arr::wrap(''));
        $this->assertEquals([''], Arr::wrap(['']));
        $this->assertEquals([false], Arr::wrap(false));
        $this->assertEquals([false], Arr::wrap([false]));
        $this->assertEquals([0], Arr::wrap(0));

        $obj = new stdClass;
        $obj->value = 'a';
        $obj = unserialize(serialize($obj));
        $this->assertEquals([$obj], Arr::wrap($obj));
        $this->assertSame($obj, Arr::wrap($obj)[0]);
    }

    public function testSortByMany()
    {
        $unsorted = [
            ['name' => 'John', 'age' => 8,  'meta' => ['key' => 3]],
            ['name' => 'John', 'age' => 10, 'meta' => ['key' => 5]],
            ['name' => 'Dave', 'age' => 10, 'meta' => ['key' => 3]],
            ['name' => 'John', 'age' => 8,  'meta' => ['key' => 2]],
        ];

        // sort using keys
        $sorted = array_values(Arr::sort($unsorted, [
            'name',
            'age',
            'meta.key',
        ]));
        $this->assertEquals([
            ['name' => 'Dave', 'age' => 10, 'meta' => ['key' => 3]],
            ['name' => 'John', 'age' => 8,  'meta' => ['key' => 2]],
            ['name' => 'John', 'age' => 8,  'meta' => ['key' => 3]],
            ['name' => 'John', 'age' => 10, 'meta' => ['key' => 5]],
        ], $sorted);

        // sort with order
        $sortedWithOrder = array_values(Arr::sort($unsorted, [
            'name',
            ['age', false],
            ['meta.key', true],
        ]));
        $this->assertEquals([
            ['name' => 'Dave', 'age' => 10, 'meta' => ['key' => 3]],
            ['name' => 'John', 'age' => 10, 'meta' => ['key' => 5]],
            ['name' => 'John', 'age' => 8,  'meta' => ['key' => 2]],
            ['name' => 'John', 'age' => 8,  'meta' => ['key' => 3]],
        ], $sortedWithOrder);

        // sort using callable
        $sortedWithCallable = array_values(Arr::sort($unsorted, [
            function ($a, $b) {
                return $a['name'] <=> $b['name'];
            },
            function ($a, $b) {
                return $b['age'] <=> $a['age'];
            },
            ['meta.key', true],
        ]));
        $this->assertEquals([
            ['name' => 'Dave', 'age' => 10, 'meta' => ['key' => 3]],
            ['name' => 'John', 'age' => 10, 'meta' => ['key' => 5]],
            ['name' => 'John', 'age' => 8,  'meta' => ['key' => 2]],
            ['name' => 'John', 'age' => 8,  'meta' => ['key' => 3]],
        ], $sortedWithCallable);
    }

    public function testKeyBy()
    {
        $array = [
            ['id' => '123', 'data' => 'abc'],
            ['id' => '345', 'data' => 'def'],
            ['id' => '498', 'data' => 'hgi'],
        ];

        $this->assertEquals([
            '123' => ['id' => '123', 'data' => 'abc'],
            '345' => ['id' => '345', 'data' => 'def'],
            '498' => ['id' => '498', 'data' => 'hgi'],
        ], Arr::keyBy($array, 'id'));
    }

    public function testPrependKeysWith()
    {
        $array = [
            'id' => '123',
            'data' => '456',
            'list' => [1, 2, 3],
            'meta' => [
                'key' => 1,
            ],
        ];

        $this->assertEquals([
            'test.id' => '123',
            'test.data' => '456',
            'test.list' => [1, 2, 3],
            'test.meta' => [
                'key' => 1,
            ],
        ], Arr::prependKeysWith($array, 'test.'));
    }

    public function testTake(): void
    {
        $array = [1, 2, 3, 4, 5, 6];

        // Test with a positive limit, should return the first 'limit' elements.
        $this->assertEquals([1, 2, 3], Arr::take($array, 3));

        // Test with a negative limit, should return the last 'abs(limit)' elements.
        $this->assertEquals([4, 5, 6], Arr::take($array, -3));

        // Test with zero limit, should return an empty array.
        $this->assertEquals([], Arr::take($array, 0));

        // Test with a limit greater than the array size, should return the entire array.
        $this->assertEquals([1, 2, 3, 4, 5, 6], Arr::take($array, 10));

        // Test with a negative limit greater than the array size, should return the entire array.
        $this->assertEquals([1, 2, 3, 4, 5, 6], Arr::take($array, -10));
    }

    public function testSelect()
    {
        $array = [
            [
                'name' => 'Taylor',
                'role' => 'Developer',
                'age' => 1,
            ],
            [
                'name' => 'Abigail',
                'role' => 'Infrastructure',
                'age' => 2,
            ],
        ];

        $this->assertEquals([
            [
                'name' => 'Taylor',
                'age' => 1,
            ],
            [
                'name' => 'Abigail',
                'age' => 2,
            ],
        ], Arr::select($array, ['name', 'age']));

        $this->assertEquals([
            [
                'name' => 'Taylor',
            ],
            [
                'name' => 'Abigail',
            ],
        ], Arr::select($array, 'name'));
    }
}
