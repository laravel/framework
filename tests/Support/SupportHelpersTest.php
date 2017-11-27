<?php

namespace Illuminate\Tests\Support;

use stdClass;
use ArrayAccess;
use Mockery as m;
use RuntimeException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\Optional;

class SupportHelpersTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testArrayDot()
    {
        $array = Arr::dot(['name' => 'taylor', 'languages' => ['php' => true]]);
        $this->assertEquals($array, ['name' => 'taylor', 'languages.php' => true]);
    }

    public function testArrayGet()
    {
        $array = ['names' => ['developer' => 'taylor']];
        $this->assertEquals('taylor', Arr::get($array, 'names.developer'));
        $this->assertEquals('dayle', Arr::get($array, 'names.otherDeveloper', 'dayle'));
        $this->assertEquals('dayle', Arr::get($array, 'names.otherDeveloper', function () {
            return 'dayle';
        }));
    }

    public function testArrayHas()
    {
        $array = ['names' => ['developer' => 'taylor']];
        $this->assertTrue(Arr::has($array, 'names'));
        $this->assertTrue(Arr::has($array, 'names.developer'));
        $this->assertFalse(Arr::has($array, 'foo'));
        $this->assertFalse(Arr::has($array, 'foo.bar'));
    }

    public function testArraySet()
    {
        $array = [];
        Arr::set($array, 'names.developer', 'taylor');
        $this->assertEquals('taylor', $array['names']['developer']);
    }

    public function testArrayForget()
    {
        $array = ['names' => ['developer' => 'taylor', 'otherDeveloper' => 'dayle']];
        Arr::forget($array, 'names.developer');
        $this->assertFalse(isset($array['names']['developer']));
        $this->assertTrue(isset($array['names']['otherDeveloper']));

        $array = ['names' => ['developer' => 'taylor', 'otherDeveloper' => 'dayle', 'thirdDeveloper' => 'Lucas']];
        Arr::forget($array, ['names.developer', 'names.otherDeveloper']);
        $this->assertFalse(isset($array['names']['developer']));
        $this->assertFalse(isset($array['names']['otherDeveloper']));
        $this->assertTrue(isset($array['names']['thirdDeveloper']));

        $array = ['names' => ['developer' => 'taylor', 'otherDeveloper' => 'dayle'], 'otherNames' => ['developer' => 'Lucas', 'otherDeveloper' => 'Graham']];
        Arr::forget($array, ['names.developer', 'otherNames.otherDeveloper']);
        $expected = ['names' => ['otherDeveloper' => 'dayle'], 'otherNames' => ['developer' => 'Lucas']];
        $this->assertEquals($expected, $array);
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

    public function testArrayExcept()
    {
        $array = ['name' => 'taylor', 'age' => 26];
        $this->assertEquals(['age' => 26], Arr::except($array, ['name']));
        $this->assertEquals(['age' => 26], Arr::except($array, 'name'));

        $array = ['name' => 'taylor', 'framework' => ['language' => 'PHP', 'name' => 'Laravel']];
        $this->assertEquals(['name' => 'taylor'], Arr::except($array, 'framework'));
        $this->assertEquals(['name' => 'taylor', 'framework' => ['name' => 'Laravel']], Arr::except($array, 'framework.language'));
        $this->assertEquals(['framework' => ['language' => 'PHP']], Arr::except($array, ['name', 'framework.name']));
    }

    public function testArrayOnly()
    {
        $array = ['name' => 'taylor', 'age' => 26];
        $this->assertEquals(['name' => 'taylor'], Arr::only($array, ['name']));
        $this->assertEmpty(Arr::only($array, ['nonExistingKey']));
    }

    public function testArrayCollapse()
    {
        $array = [[1], [2], [3], ['foo', 'bar'], collect(['baz', 'boom'])];
        $this->assertEquals([1, 2, 3, 'foo', 'bar', 'baz', 'boom'], Arr::collapse($array));
    }

    public function testArrayDivide()
    {
        $array = ['name' => 'taylor'];
        list($keys, $values) = Arr::divide($array);
        $this->assertEquals(['name'], $keys);
        $this->assertEquals(['taylor'], $values);
    }

    public function testArrayFirst()
    {
        $array = ['name' => 'taylor', 'otherDeveloper' => 'dayle'];
        $this->assertEquals('dayle', Arr::first($array, function ($value) {
            return $value == 'dayle';
        }));
    }

    public function testArrayLast()
    {
        $array = [100, 250, 290, 320, 500, 560, 670];
        $this->assertEquals(670, Arr::last($array, function ($value) {
            return $value > 320;
        }));
    }

    public function testArrayPluck()
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
    }

    public function testArrayPrepend()
    {
        $array = Arr::prepend(['one', 'two', 'three', 'four'], 'zero');
        $this->assertEquals(['zero', 'one', 'two', 'three', 'four'], $array);

        $array = Arr::prepend(['one' => 1, 'two' => 2], 0, 'zero');
        $this->assertEquals(['zero' => 0, 'one' => 1, 'two' => 2], $array);
    }

    public function testArrayFlatten()
    {
        $this->assertEquals(['#foo', '#bar', '#baz'], Arr::flatten([['#foo', '#bar'], ['#baz']]));
    }

    public function testStrIs()
    {
        $this->assertTrue(Str::is('*.dev', 'localhost.dev'));
        $this->assertTrue(Str::is('a', 'a'));
        $this->assertTrue(Str::is('/', '/'));
        $this->assertTrue(Str::is('*dev*', 'localhost.dev'));
        $this->assertTrue(Str::is('foo?bar', 'foo?bar'));
        $this->assertFalse(Str::is('*something', 'foobar'));
        $this->assertFalse(Str::is('foo', 'bar'));
        $this->assertFalse(Str::is('foo.*', 'foobar'));
        $this->assertFalse(Str::is('foo.ar', 'foobar'));
        $this->assertFalse(Str::is('foo?bar', 'foobar'));
        $this->assertFalse(Str::is('foo?bar', 'fobar'));

        $this->assertTrue(Str::is([
            '*.dev',
            '*oc*',
        ], 'localhost.dev'));

        $this->assertFalse(Str::is([
            '/',
            'a*',
        ], 'localhost.dev'));

        $this->assertFalse(Str::is([], 'localhost.dev'));
    }

    public function testStrRandom()
    {
        $result = Str::random(20);
        $this->assertInternalType('string', $result);
        $this->assertEquals(20, strlen($result));
    }

    public function testStartsWith()
    {
        $this->assertTrue(Str::startsWith('jason', 'jas'));
        $this->assertTrue(Str::startsWith('jason', ['jas']));
        $this->assertFalse(Str::startsWith('jason', 'day'));
        $this->assertFalse(Str::startsWith('jason', ['day']));
    }

    public function testE()
    {
        $str = 'A \'quote\' is <b>bold</b>';
        $this->assertEquals('A &#039;quote&#039; is &lt;b&gt;bold&lt;/b&gt;', e($str));
        $html = m::mock('Illuminate\Contracts\Support\Htmlable');
        $html->shouldReceive('toHtml')->andReturn($str);
        $this->assertEquals($str, e($html));
    }

    public function testEndsWith()
    {
        $this->assertTrue(Str::endsWith('jason', 'on'));
        $this->assertTrue(Str::endsWith('jason', ['on']));
        $this->assertFalse(Str::endsWith('jason', 'no'));
        $this->assertFalse(Str::endsWith('jason', ['no']));
    }

    public function testStrAfter()
    {
        $this->assertEquals('nah', str_after('hannah', 'han'));
        $this->assertEquals('nah', str_after('hannah', 'n'));
        $this->assertEquals('hannah', str_after('hannah', 'xxxx'));
    }

    public function testStrContains()
    {
        $this->assertTrue(Str::contains('taylor', 'ylo'));
        $this->assertTrue(Str::contains('taylor', ['ylo']));
        $this->assertFalse(Str::contains('taylor', 'xxx'));
        $this->assertFalse(Str::contains('taylor', ['xxx']));
        $this->assertTrue(Str::contains('taylor', ['xxx', 'taylor']));
    }

    public function testStrFinish()
    {
        $this->assertEquals('test/string/', Str::finish('test/string', '/'));
        $this->assertEquals('test/string/', Str::finish('test/string/', '/'));
        $this->assertEquals('test/string/', Str::finish('test/string//', '/'));
    }

    public function testStrStart()
    {
        $this->assertEquals('/test/string', Str::start('test/string', '/'));
        $this->assertEquals('/test/string', Str::start('/test/string', '/'));
        $this->assertEquals('/test/string', Str::start('//test/string', '/'));
    }

    public function testSnakeCase()
    {
        $this->assertEquals('foo_bar', Str::snake('fooBar'));
        $this->assertEquals('foo_bar', Str::snake('fooBar')); // test cache
    }

    public function testStrLimit()
    {
        $string = 'The PHP framework for web artisans.';
        $this->assertEquals('The PHP...', Str::limit($string, 7));
        $this->assertEquals('The PHP', Str::limit($string, 7, ''));
        $this->assertEquals('The PHP framework for web artisans.', Str::limit($string, 100));

        $nonAsciiString = '这是一段中文';
        $this->assertEquals('这是一...', Str::limit($nonAsciiString, 6));
        $this->assertEquals('这是一', Str::limit($nonAsciiString, 6, ''));
    }

    public function testCamelCase()
    {
        $this->assertEquals('fooBar', Str::camel('FooBar'));
        $this->assertEquals('fooBar', Str::camel('foo_bar'));
        $this->assertEquals('fooBar', Str::camel('foo_bar')); // test cache
        $this->assertEquals('fooBarBaz', Str::camel('Foo-barBaz'));
        $this->assertEquals('fooBarBaz', Str::camel('foo-bar_baz'));
    }

    public function testStudlyCase()
    {
        $this->assertEquals('FooBar', Str::studly('fooBar'));
        $this->assertEquals('FooBar', Str::studly('foo_bar'));
        $this->assertEquals('FooBar', Str::studly('foo_bar')); // test cache
        $this->assertEquals('FooBarBaz', Str::studly('foo-barBaz'));
        $this->assertEquals('FooBarBaz', Str::studly('foo-bar_baz'));
    }

    public function testClassBasename()
    {
        $this->assertEquals('Baz', class_basename('Foo\Bar\Baz'));
        $this->assertEquals('Baz', class_basename('Baz'));
    }

    public function testValue()
    {
        $this->assertEquals('foo', value('foo'));
        $this->assertEquals('foo', value(function () {
            return 'foo';
        }));
    }

    public function testObjectGet()
    {
        $class = new stdClass;
        $class->name = new stdClass;
        $class->name->first = 'Taylor';

        $this->assertEquals('Taylor', object_get($class, 'name.first'));
    }

    public function testDataGet()
    {
        $object = (object) ['users' => ['name' => ['Taylor', 'Otwell']]];
        $array = [(object) ['users' => [(object) ['name' => 'Taylor']]]];
        $dottedArray = ['users' => ['first.name' => 'Taylor', 'middle.name' => null]];
        $arrayAccess = new SupportTestArrayAccess(['price' => 56, 'user' => new SupportTestArrayAccess(['name' => 'John']), 'email' => null]);

        $this->assertEquals('Taylor', data_get($object, 'users.name.0'));
        $this->assertEquals('Taylor', data_get($array, '0.users.0.name'));
        $this->assertNull(data_get($array, '0.users.3'));
        $this->assertEquals('Not found', data_get($array, '0.users.3', 'Not found'));
        $this->assertEquals('Not found', data_get($array, '0.users.3', function () {
            return 'Not found';
        }));
        $this->assertEquals('Taylor', data_get($dottedArray, ['users', 'first.name']));
        $this->assertNull(data_get($dottedArray, ['users', 'middle.name']));
        $this->assertEquals('Not found', data_get($dottedArray, ['users', 'last.name'], 'Not found'));
        $this->assertEquals(56, data_get($arrayAccess, 'price'));
        $this->assertEquals('John', data_get($arrayAccess, 'user.name'));
        $this->assertEquals('void', data_get($arrayAccess, 'foo', 'void'));
        $this->assertEquals('void', data_get($arrayAccess, 'user.foo', 'void'));
        $this->assertNull(data_get($arrayAccess, 'foo'));
        $this->assertNull(data_get($arrayAccess, 'user.foo'));
        $this->assertNull(data_get($arrayAccess, 'email', 'Not found'));
    }

    public function testDataGetWithNestedArrays()
    {
        $array = [
            ['name' => 'taylor', 'email' => 'taylorotwell@gmail.com'],
            ['name' => 'abigail'],
            ['name' => 'dayle'],
        ];

        $this->assertEquals(['taylor', 'abigail', 'dayle'], data_get($array, '*.name'));
        $this->assertEquals(['taylorotwell@gmail.com', null, null], data_get($array, '*.email', 'irrelevant'));

        $array = [
            'users' => [
                ['first' => 'taylor', 'last' => 'otwell', 'email' => 'taylorotwell@gmail.com'],
                ['first' => 'abigail', 'last' => 'otwell'],
                ['first' => 'dayle', 'last' => 'rees'],
            ],
            'posts' => null,
        ];

        $this->assertEquals(['taylor', 'abigail', 'dayle'], data_get($array, 'users.*.first'));
        $this->assertEquals(['taylorotwell@gmail.com', null, null], data_get($array, 'users.*.email', 'irrelevant'));
        $this->assertEquals('not found', data_get($array, 'posts.*.date', 'not found'));
        $this->assertNull(data_get($array, 'posts.*.date'));
    }

    public function testDataGetWithDoubleNestedArraysCollapsesResult()
    {
        $array = [
            'posts' => [
                [
                    'comments' => [
                        ['author' => 'taylor', 'likes' => 4],
                        ['author' => 'abigail', 'likes' => 3],
                    ],
                ],
                [
                    'comments' => [
                        ['author' => 'abigail', 'likes' => 2],
                        ['author' => 'dayle'],
                    ],
                ],
                [
                    'comments' => [
                        ['author' => 'dayle'],
                        ['author' => 'taylor', 'likes' => 1],
                    ],
                ],
            ],
        ];

        $this->assertEquals(['taylor', 'abigail', 'abigail', 'dayle', 'dayle', 'taylor'], data_get($array, 'posts.*.comments.*.author'));
        $this->assertEquals([4, 3, 2, null, null, 1], data_get($array, 'posts.*.comments.*.likes'));
        $this->assertEquals([], data_get($array, 'posts.*.users.*.name', 'irrelevant'));
        $this->assertEquals([], data_get($array, 'posts.*.users.*.name'));
    }

    public function testDataFill()
    {
        $data = ['foo' => 'bar'];

        $this->assertEquals(['foo' => 'bar', 'baz' => 'boom'], data_fill($data, 'baz', 'boom'));
        $this->assertEquals(['foo' => 'bar', 'baz' => 'boom'], data_fill($data, 'baz', 'noop'));
        $this->assertEquals(['foo' => [], 'baz' => 'boom'], data_fill($data, 'foo.*', 'noop'));
        $this->assertEquals(
            ['foo' => ['bar' => 'kaboom'], 'baz' => 'boom'],
            data_fill($data, 'foo.bar', 'kaboom')
        );
    }

    public function testDataFillWithStar()
    {
        $data = ['foo' => 'bar'];

        $this->assertEquals(
            ['foo' => []],
            data_fill($data, 'foo.*.bar', 'noop')
        );

        $this->assertEquals(
            ['foo' => [], 'bar' => [['baz' => 'original'], []]],
            data_fill($data, 'bar', [['baz' => 'original'], []])
        );

        $this->assertEquals(
            ['foo' => [], 'bar' => [['baz' => 'original'], ['baz' => 'boom']]],
            data_fill($data, 'bar.*.baz', 'boom')
        );

        $this->assertEquals(
            ['foo' => [], 'bar' => [['baz' => 'original'], ['baz' => 'boom']]],
            data_fill($data, 'bar.*', 'noop')
        );
    }

    public function testDataFillWithDoubleStar()
    {
        $data = [
            'posts' => [
                (object) [
                    'comments' => [
                        (object) ['name' => 'First'],
                        (object) [],
                    ],
                ],
                (object) [
                    'comments' => [
                        (object) [],
                        (object) ['name' => 'Second'],
                    ],
                ],
            ],
        ];

        data_fill($data, 'posts.*.comments.*.name', 'Filled');

        $this->assertEquals([
            'posts' => [
                (object) [
                    'comments' => [
                        (object) ['name' => 'First'],
                        (object) ['name' => 'Filled'],
                    ],
                ],
                (object) [
                    'comments' => [
                        (object) ['name' => 'Filled'],
                        (object) ['name' => 'Second'],
                    ],
                ],
            ],
        ], $data);
    }

    public function testDataSet()
    {
        $data = ['foo' => 'bar'];

        $this->assertEquals(
            ['foo' => 'bar', 'baz' => 'boom'],
            data_set($data, 'baz', 'boom')
        );

        $this->assertEquals(
            ['foo' => 'bar', 'baz' => 'kaboom'],
            data_set($data, 'baz', 'kaboom')
        );

        $this->assertEquals(
            ['foo' => [], 'baz' => 'kaboom'],
            data_set($data, 'foo.*', 'noop')
        );

        $this->assertEquals(
            ['foo' => ['bar' => 'boom'], 'baz' => 'kaboom'],
            data_set($data, 'foo.bar', 'boom')
        );

        $this->assertEquals(
            ['foo' => ['bar' => 'boom'], 'baz' => ['bar' => 'boom']],
            data_set($data, 'baz.bar', 'boom')
        );

        $this->assertEquals(
            ['foo' => ['bar' => 'boom'], 'baz' => ['bar' => ['boom' => ['kaboom' => 'boom']]]],
            data_set($data, 'baz.bar.boom.kaboom', 'boom')
        );
    }

    public function testDataSetWithStar()
    {
        $data = ['foo' => 'bar'];

        $this->assertEquals(
            ['foo' => []],
            data_set($data, 'foo.*.bar', 'noop')
        );

        $this->assertEquals(
            ['foo' => [], 'bar' => [['baz' => 'original'], []]],
            data_set($data, 'bar', [['baz' => 'original'], []])
        );

        $this->assertEquals(
            ['foo' => [], 'bar' => [['baz' => 'boom'], ['baz' => 'boom']]],
            data_set($data, 'bar.*.baz', 'boom')
        );

        $this->assertEquals(
            ['foo' => [], 'bar' => ['overwritten', 'overwritten']],
            data_set($data, 'bar.*', 'overwritten')
        );
    }

    public function testDataSetWithDoubleStar()
    {
        $data = [
            'posts' => [
                (object) [
                    'comments' => [
                        (object) ['name' => 'First'],
                        (object) [],
                    ],
                ],
                (object) [
                    'comments' => [
                        (object) [],
                        (object) ['name' => 'Second'],
                    ],
                ],
            ],
        ];

        data_set($data, 'posts.*.comments.*.name', 'Filled');

        $this->assertEquals([
            'posts' => [
                (object) [
                    'comments' => [
                        (object) ['name' => 'Filled'],
                        (object) ['name' => 'Filled'],
                    ],
                ],
                (object) [
                    'comments' => [
                        (object) ['name' => 'Filled'],
                        (object) ['name' => 'Filled'],
                    ],
                ],
            ],
        ], $data);
    }

    public function testArraySort()
    {
        $array = [
            ['name' => 'baz'],
            ['name' => 'foo'],
            ['name' => 'bar'],
        ];

        $this->assertEquals([
            ['name' => 'bar'],
            ['name' => 'baz'],
            ['name' => 'foo'], ],
        array_values(Arr::sort($array, function ($v) {
            return $v['name'];
        })));
    }

    public function testArraySortRecursive()
    {
        $array = [
            [
                'foo',
                'bar',
                'baz',
            ],
            [
                'baz',
                'foo',
                'bar',
            ],
        ];

        $assumedArray = [
            [
                'bar',
                'baz',
                'foo',
            ],
            [
                'bar',
                'baz',
                'foo',
            ],
        ];

        $this->assertEquals($assumedArray, Arr::sortRecursive($array));
    }

    public function testArrayWhere()
    {
        $array = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5, 'f' => 6, 'g' => 7, 'h' => 8];
        $this->assertEquals(['b' => 2, 'd' => 4, 'f' => 6, 'h' => 8], Arr::where(
            $array,
            function ($value, $key) {
                return $value % 2 === 0;
            }
        ));
    }

    public function testArrayWrap()
    {
        $string = 'a';
        $array = ['a'];
        $object = new stdClass;
        $object->value = 'a';
        $this->assertEquals(['a'], Arr::wrap($string));
        $this->assertEquals($array, Arr::wrap($array));
        $this->assertEquals([$object], Arr::wrap($object));
    }

    public function testHead()
    {
        $array = ['a', 'b', 'c'];
        $this->assertEquals('a', head($array));
    }

    public function testLast()
    {
        $array = ['a', 'b', 'c'];
        $this->assertEquals('c', last($array));
    }

    public function testClassUsesRecursiveShouldReturnTraitsOnParentClasses()
    {
        $this->assertEquals([
            'Illuminate\Tests\Support\SupportTestTraitOne' => 'Illuminate\Tests\Support\SupportTestTraitOne',
            'Illuminate\Tests\Support\SupportTestTraitTwo' => 'Illuminate\Tests\Support\SupportTestTraitTwo',
        ],
        class_uses_recursive('Illuminate\Tests\Support\SupportTestClassTwo'));
    }

    public function testClassUsesRecursiveAcceptsObject()
    {
        $this->assertEquals([
            'Illuminate\Tests\Support\SupportTestTraitOne' => 'Illuminate\Tests\Support\SupportTestTraitOne',
            'Illuminate\Tests\Support\SupportTestTraitTwo' => 'Illuminate\Tests\Support\SupportTestTraitTwo',
        ],
        class_uses_recursive(new SupportTestClassTwo));
    }

    public function testArrayAdd()
    {
        $this->assertEquals(['surname' => 'Mövsümov'], Arr::add([], 'surname', 'Mövsümov'));
        $this->assertEquals(['developer' => ['name' => 'Ferid']], Arr::add([], 'developer.name', 'Ferid'));
    }

    public function testArrayPull()
    {
        $developer = ['firstname' => 'Ferid', 'surname' => 'Mövsümov'];
        $this->assertEquals('Mövsümov', Arr::pull($developer, 'surname'));
        $this->assertEquals(['firstname' => 'Ferid'], $developer);
    }

    public function testTap()
    {
        $object = (object) ['id' => 1];
        $this->assertEquals(2, tap($object, function ($object) {
            $object->id = 2;
        })->id);

        $mock = m::mock();
        $mock->shouldReceive('foo')->once()->andReturn('bar');
        $this->assertEquals($mock, tap($mock)->foo());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testThrow()
    {
        throw_if(true, new RuntimeException);
    }

    public function testThrowReturnIfNotThrown()
    {
        $this->assertSame('foo', throw_unless('foo', new RuntimeException));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Test Message
     */
    public function testThrowWithString()
    {
        throw_if(true, RuntimeException::class, 'Test Message');
    }

    public function testOptional()
    {
        $this->assertNull(optional(null)->something());

        $this->assertEquals(10, optional(new class {
            public function something()
            {
                return 10;
            }
        })->something());
    }

    public function testOptionalIsMacroable()
    {
        Optional::macro('present', function () {
            if (is_object($this->value)) {
                return $this->value->present();
            }

            return new Optional(null);
        });

        $this->assertNull(optional(null)->present()->something());

        $this->assertEquals('$10.00', optional(new class {
            public function present()
            {
                return new class {
                    public function something()
                    {
                        return '$10.00';
                    }
                };
            }
        })->present()->something());
    }

    public function testTransform()
    {
        $this->assertEquals(10, transform(5, function ($value) {
            return $value * 2;
        }));

        $this->assertNull(transform(null, function () {
            return 10;
        }));
    }

    public function testWith()
    {
        $this->assertEquals(10, with(10));

        $this->assertEquals(10, with(5, function ($five) {
            return $five + 5;
        }));
    }
}

trait SupportTestTraitOne
{
}

trait SupportTestTraitTwo
{
    use SupportTestTraitOne;
}

class SupportTestClassOne
{
    use SupportTestTraitTwo;
}

class SupportTestClassTwo extends SupportTestClassOne
{
}

class SupportTestArrayAccess implements ArrayAccess
{
    protected $attributes = [];

    public function __construct($attributes = [])
    {
        $this->attributes = $attributes;
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->attributes);
    }

    public function offsetGet($offset)
    {
        return $this->attributes[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->attributes[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
    }
}
