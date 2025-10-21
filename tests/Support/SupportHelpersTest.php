<?php

namespace Illuminate\Tests\Support;

use ArrayAccess;
use ArrayIterator;
use Countable;
use Error;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Env;
use Illuminate\Support\Optional;
use Illuminate\Support\Sleep;
use Illuminate\Support\Stringable;
use Illuminate\Tests\Support\Fixtures\IntBackedEnum;
use Illuminate\Tests\Support\Fixtures\StringBackedEnum;
use IteratorAggregate;
use LogicException;
use Mockery as m;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use stdClass;
use Traversable;

class SupportHelpersTest extends TestCase
{
    protected function setUp(): void
    {
        mkdir(__DIR__.'/tmp');

        parent::setUp();
    }

    protected function tearDown(): void
    {
        m::close();

        if (is_dir(__DIR__.'/tmp')) {
            (new Filesystem)->deleteDirectory(__DIR__.'/tmp');
        }

        parent::tearDown();
    }

    public function testE()
    {
        $str = 'A \'quote\' is <b>bold</b>';
        $this->assertSame('A &#039;quote&#039; is &lt;b&gt;bold&lt;/b&gt;', e($str));

        $html = m::mock(Htmlable::class);
        $html->shouldReceive('toHtml')->andReturn($str);
        $this->assertEquals($str, e($html));
    }

    public function testEWithInvalidCodePoints()
    {
        $str = mb_convert_encoding('føø bar', 'ISO-8859-1', 'UTF-8');
        $this->assertEquals('f�� bar', e($str));
    }

    public function testEWithEnums()
    {
        $enumValue = StringBackedEnum::ADMIN_LABEL;
        $this->assertSame('I am &#039;admin&#039;', e($enumValue));

        $enumValue = IntBackedEnum::ROLE_ADMIN;
        $this->assertSame('1', e($enumValue));
    }

    public function testBlank()
    {
        $this->assertTrue(blank(null));
        $this->assertTrue(blank(''));
        $this->assertTrue(blank('  '));
        $this->assertTrue(blank(new Stringable('')));
        $this->assertTrue(blank(new Stringable('  ')));
        $this->assertFalse(blank(10));
        $this->assertFalse(blank(true));
        $this->assertFalse(blank(false));
        $this->assertFalse(blank(0));
        $this->assertFalse(blank(0.0));
        $this->assertFalse(blank(new Stringable(' FooBar ')));

        $object = new SupportTestCountable();
        $this->assertTrue(blank($object));
    }

    public function testBlankDoesntJsonSerializeModels()
    {
        $model = new class extends Model
        {
            public function jsonSerialize(): mixed
            {
                throw new RuntimeException('Model should not be serialized');
            }
        };

        $this->assertFalse(blank($model));
    }

    public function testClassBasename()
    {
        $this->assertSame('Baz', class_basename('Foo\Bar\Baz'));
        $this->assertSame('Baz', class_basename('Baz'));
        // back-slash
        $this->assertSame('Baz', class_basename('\Baz'));
        $this->assertSame('Baz', class_basename('\\\\Baz\\'));
        $this->assertSame('Baz', class_basename('\Foo\Bar\Baz\\'));
        $this->assertSame('Baz', class_basename('\Foo/Bar\Baz/'));
        // forward-slash
        $this->assertSame('Baz', class_basename('/Foo/Bar/Baz/'));
        $this->assertSame('Baz', class_basename('/Foo///Bar/Baz//'));
        // accepts objects
        $this->assertSame('stdClass', class_basename(new stdClass()));
        // edge-cases
        $this->assertSame('1', class_basename(1));
        $this->assertSame('1', class_basename('1'));
        $this->assertSame('', class_basename(''));
        $this->assertSame('', class_basename('\\'));
        $this->assertSame('', class_basename('\\\\'));
        $this->assertSame('', class_basename('/'));
        $this->assertSame('', class_basename('///'));
        $this->assertSame('..', class_basename('\Foo\Bar\Baz\\..\\'));
    }

    public function testWhen()
    {
        $this->assertEquals('Hello', when(true, 'Hello'));
        $this->assertNull(when(false, 'Hello'));
        $this->assertEquals('There', when(1 === 1, 'There')); // strict types
        $this->assertEquals('There', when(1 == '1', 'There')); // loose types
        $this->assertNull(when(1 == 2, 'There'));
        $this->assertNull(when('1', fn () => null));
        $this->assertNull(when(0, fn () => null));
        $this->assertEquals('True', when([1, 2, 3, 4], 'True')); // Array
        $this->assertNull(when([], 'True')); // Empty Array = Falsy
        $this->assertEquals('True', when(new StdClass, fn () => 'True')); // Object
        $this->assertEquals('World', when(false, 'Hello', 'World'));
        $this->assertEquals('World', when(1 === 0, 'Hello', 'World')); // strict types
        $this->assertEquals('World', when(1 == '0', 'Hello', 'World')); // loose types
        $this->assertNull(when('', fn () => 'There', fn () => null));
        $this->assertNull(when(0, fn () => 'There', fn () => null));
        $this->assertEquals('False', when([], 'True', 'False'));  // Empty Array = Falsy
        $this->assertTrue(when(true, fn ($value) => $value, fn ($value) => ! $value)); // lazy evaluation
        $this->assertTrue(when(false, fn ($value) => $value, fn ($value) => ! $value)); // lazy evaluation
        $this->assertEquals('Hello', when(fn () => true, 'Hello')); // lazy evaluation condition
        $this->assertEquals('World', when(fn () => false, 'Hello', 'World')); // lazy evaluation condition
    }

    public function testFilled()
    {
        $this->assertFalse(filled(null));
        $this->assertFalse(filled(''));
        $this->assertFalse(filled('  '));
        $this->assertFalse(filled(new Stringable('')));
        $this->assertFalse(filled(new Stringable('  ')));
        $this->assertTrue(filled(10));
        $this->assertTrue(filled(true));
        $this->assertTrue(filled(false));
        $this->assertTrue(filled(0));
        $this->assertTrue(filled(0.0));
        $this->assertTrue(filled(new Stringable(' FooBar ')));

        $object = new SupportTestCountable();
        $this->assertFalse(filled($object));
    }

    public function testValue()
    {
        $callable = new class
        {
            public function __call($method, $arguments)
            {
                return $arguments;
            }
        };

        $this->assertSame($callable, value($callable, 'foo'));
        $this->assertSame('foo', value('foo'));
        $this->assertSame('foo', value(function () {
            return 'foo';
        }));
        $this->assertSame('foo', value(function ($arg) {
            return $arg;
        }, 'foo'));
    }

    public function testObjectGet()
    {
        $class = new stdClass;
        $class->name = new stdClass;
        $class->name->first = 'Taylor';

        $this->assertSame('Taylor', object_get($class, 'name.first'));
        $this->assertSame('Taylor', object_get($class, 'name.first', 'default'));
    }

    public function testObjectGetDefaultValue()
    {
        $class = new stdClass;
        $class->name = new stdClass;
        $class->name->first = 'Taylor';

        $this->assertSame('default', object_get($class, 'name.family', 'default'));
        $this->assertNull(object_get($class, 'name.family'));
    }

    public function testObjectGetWhenKeyIsNullOrEmpty()
    {
        $object = new stdClass;

        $this->assertEquals($object, object_get($object, null));
        $this->assertEquals($object, object_get($object, false));
        $this->assertEquals($object, object_get($object, ''));
        $this->assertEquals($object, object_get($object, '  '));
    }

    public function testDataGet()
    {
        $object = (object) ['users' => ['name' => ['Taylor', 'Otwell']]];
        $array = [(object) ['users' => [(object) ['name' => 'Taylor']]]];
        $dottedArray = ['users' => ['first.name' => 'Taylor', 'middle.name' => null]];
        $arrayAccess = new SupportTestArrayAccess(['price' => 56, 'user' => new SupportTestArrayAccess(['name' => 'John']), 'email' => null]);

        $this->assertSame('Taylor', data_get($object, 'users.name.0'));
        $this->assertSame('Taylor', data_get($array, '0.users.0.name'));
        $this->assertNull(data_get($array, '0.users.3'));
        $this->assertSame('Not found', data_get($array, '0.users.3', 'Not found'));
        $this->assertSame('Not found', data_get($array, '0.users.3', function () {
            return 'Not found';
        }));
        $this->assertSame('Taylor', data_get($dottedArray, ['users', 'first.name']));
        $this->assertNull(data_get($dottedArray, ['users', 'middle.name']));
        $this->assertSame('Not found', data_get($dottedArray, ['users', 'last.name'], 'Not found'));
        $this->assertEquals(56, data_get($arrayAccess, 'price'));
        $this->assertSame('John', data_get($arrayAccess, 'user.name'));
        $this->assertSame('void', data_get($arrayAccess, 'foo', 'void'));
        $this->assertSame('void', data_get($arrayAccess, 'user.foo', 'void'));
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
        $arrayIterable = new SupportTestArrayIterable([
            ['name' => 'taylor', 'email' => 'taylorotwell@gmail.com'],
            ['name' => 'abigail'],
            ['name' => 'dayle'],
        ]);

        $this->assertEquals(['taylor', 'abigail', 'dayle'], data_get($array, '*.name'));
        $this->assertEquals(['taylorotwell@gmail.com', null, null], data_get($array, '*.email', 'irrelevant'));

        $this->assertEquals(['taylor', 'abigail', 'dayle'], data_get($arrayIterable, '*.name'));
        $this->assertEquals(['taylorotwell@gmail.com', null, null], data_get($arrayIterable, '*.email', 'irrelevant'));

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
        $this->assertSame('not found', data_get($array, 'posts.*.date', 'not found'));
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

    public function testDataGetFirstLastDirectives()
    {
        $array = [
            'flights' => [
                [
                    'segments' => [
                        ['from' => 'LHR', 'departure' => '9:00', 'to' => 'IST', 'arrival' => '15:00'],
                        ['from' => 'IST', 'departure' => '16:00', 'to' => 'PKX', 'arrival' => '20:00'],
                    ],
                ],
                [
                    'segments' => [
                        ['from' => 'LGW', 'departure' => '8:00', 'to' => 'SAW', 'arrival' => '14:00'],
                        ['from' => 'SAW', 'departure' => '15:00', 'to' => 'PEK', 'arrival' => '19:00'],
                    ],
                ],
            ],
            'empty' => [],
        ];

        $this->assertEquals('LHR', data_get($array, 'flights.0.segments.{first}.from'));
        $this->assertEquals('PKX', data_get($array, 'flights.0.segments.{last}.to'));

        $this->assertEquals('LHR', data_get($array, 'flights.{first}.segments.{first}.from'));
        $this->assertEquals('PEK', data_get($array, 'flights.{last}.segments.{last}.to'));
        $this->assertEquals('PKX', data_get($array, 'flights.{first}.segments.{last}.to'));
        $this->assertEquals('LGW', data_get($array, 'flights.{last}.segments.{first}.from'));

        $this->assertEquals(['LHR', 'IST'], data_get($array, 'flights.{first}.segments.*.from'));
        $this->assertEquals(['SAW', 'PEK'], data_get($array, 'flights.{last}.segments.*.to'));

        $this->assertEquals(['LHR', 'LGW'], data_get($array, 'flights.*.segments.{first}.from'));
        $this->assertEquals(['PKX', 'PEK'], data_get($array, 'flights.*.segments.{last}.to'));

        $this->assertEquals('Not found', data_get($array, 'empty.{first}', 'Not found'));
        $this->assertEquals('Not found', data_get($array, 'empty.{last}', 'Not found'));
    }

    public function testDataGetFirstLastDirectivesOnArrayAccessIterable()
    {
        $arrayAccessIterable = [
            'flights' => new SupportTestArrayAccessIterable([
                [
                    'segments' => new SupportTestArrayAccessIterable([
                        ['from' => 'LHR', 'departure' => '9:00', 'to' => 'IST', 'arrival' => '15:00'],
                        ['from' => 'IST', 'departure' => '16:00', 'to' => 'PKX', 'arrival' => '20:00'],
                    ]),
                ],
                [
                    'segments' => new SupportTestArrayAccessIterable([
                        ['from' => 'LGW', 'departure' => '8:00', 'to' => 'SAW', 'arrival' => '14:00'],
                        ['from' => 'SAW', 'departure' => '15:00', 'to' => 'PEK', 'arrival' => '19:00'],
                    ]),
                ],
            ]),
            'empty' => new SupportTestArrayAccessIterable([]),
        ];

        $this->assertEquals('LHR', data_get($arrayAccessIterable, 'flights.0.segments.{first}.from'));
        $this->assertEquals('PKX', data_get($arrayAccessIterable, 'flights.0.segments.{last}.to'));

        $this->assertEquals('LHR', data_get($arrayAccessIterable, 'flights.{first}.segments.{first}.from'));
        $this->assertEquals('PEK', data_get($arrayAccessIterable, 'flights.{last}.segments.{last}.to'));
        $this->assertEquals('PKX', data_get($arrayAccessIterable, 'flights.{first}.segments.{last}.to'));
        $this->assertEquals('LGW', data_get($arrayAccessIterable, 'flights.{last}.segments.{first}.from'));

        $this->assertEquals(['LHR', 'IST'], data_get($arrayAccessIterable, 'flights.{first}.segments.*.from'));
        $this->assertEquals(['SAW', 'PEK'], data_get($arrayAccessIterable, 'flights.{last}.segments.*.to'));

        $this->assertEquals(['LHR', 'LGW'], data_get($arrayAccessIterable, 'flights.*.segments.{first}.from'));
        $this->assertEquals(['PKX', 'PEK'], data_get($arrayAccessIterable, 'flights.*.segments.{last}.to'));

        $this->assertEquals('Not found', data_get($arrayAccessIterable, 'empty.{first}', 'Not found'));
        $this->assertEquals('Not found', data_get($arrayAccessIterable, 'empty.{last}', 'Not found'));
    }

    public function testDataGetFirstLastDirectivesOnKeyedArrays()
    {
        $array = [
            'numericKeys' => [
                2 => 'first',
                0 => 'second',
                1 => 'last',
            ],
            'stringKeys' => [
                'one' => 'first',
                'two' => 'second',
                'three' => 'last',
            ],
        ];

        $this->assertEquals('second', data_get($array, 'numericKeys.0'));
        $this->assertEquals('first', data_get($array, 'numericKeys.{first}'));
        $this->assertEquals('last', data_get($array, 'numericKeys.{last}'));
        $this->assertEquals('first', data_get($array, 'stringKeys.{first}'));
        $this->assertEquals('last', data_get($array, 'stringKeys.{last}'));
    }

    public function testDataGetEscapedSegmentKeys()
    {
        $array = [
            'symbols' => [
                '{last}' => ['description' => 'dollar'],
                '*' => ['description' => 'asterisk'],
                '{first}' => ['description' => 'caret'],
            ],
        ];

        $this->assertEquals('caret', data_get($array, 'symbols.\{first}.description'));
        $this->assertEquals('dollar', data_get($array, 'symbols.{first}.description'));
        $this->assertEquals('asterisk', data_get($array, 'symbols.\*.description'));
        $this->assertEquals(['dollar', 'asterisk', 'caret'], data_get($array, 'symbols.*.description'));
        $this->assertEquals('dollar', data_get($array, 'symbols.\{last}.description'));
        $this->assertEquals('caret', data_get($array, 'symbols.{last}.description'));
    }

    public function testDataGetStar()
    {
        $data = ['foo' => 'bar'];
        $this->assertEquals(['bar'], data_get($data, '*'));

        $data = collect(['foo' => 'bar']);
        $this->assertEquals(['bar'], data_get($data, '*'));
    }

    public function testDataGetNullKey()
    {
        $data = ['foo' => 'bar'];

        $this->assertEquals(['foo' => 'bar'], data_get($data, null));
        $this->assertEquals(['foo' => 'bar'], data_get($data, null, '42'));
        $this->assertEquals(['foo' => 'bar'], data_get($data, [null]));

        $data = ['foo' => 'bar', 'baz' => 42];
        $this->assertEquals(['foo' => 'bar', 'baz' => 42], data_get($data, [null, 'foo']));
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

    public function testDataRemove()
    {
        $data = ['foo' => 'bar', 'hello' => 'world'];

        $this->assertEquals(
            ['hello' => 'world'],
            data_forget($data, 'foo')
        );

        $data = ['foo' => 'bar', 'hello' => 'world'];

        $this->assertEquals(
            ['foo' => 'bar', 'hello' => 'world'],
            data_forget($data, 'nothing')
        );

        $data = ['one' => ['two' => ['three' => 'hello', 'four' => ['five']]]];

        $this->assertEquals(
            ['one' => ['two' => ['four' => ['five']]]],
            data_forget($data, 'one.two.three')
        );
    }

    public function testDataRemoveWithStar()
    {
        $data = [
            'article' => [
                'title' => 'Foo',
                'comments' => [
                    ['comment' => 'foo', 'name' => 'First'],
                    ['comment' => 'bar', 'name' => 'Second'],
                ],
            ],
        ];

        $this->assertEquals(
            [
                'article' => [
                    'title' => 'Foo',
                    'comments' => [
                        ['comment' => 'foo'],
                        ['comment' => 'bar'],
                    ],
                ],
            ],
            data_forget($data, 'article.comments.*.name')
        );
    }

    public function testDataRemoveWithDoubleStar()
    {
        $data = [
            'posts' => [
                (object) [
                    'comments' => [
                        (object) ['name' => 'First', 'comment' => 'foo'],
                        (object) ['name' => 'Second', 'comment' => 'bar'],
                    ],
                ],
                (object) [
                    'comments' => [
                        (object) ['name' => 'Third', 'comment' => 'hello'],
                        (object) ['name' => 'Fourth', 'comment' => 'world'],
                    ],
                ],
            ],
        ];

        data_forget($data, 'posts.*.comments.*.name');

        $this->assertEquals([
            'posts' => [
                (object) [
                    'comments' => [
                        (object) ['comment' => 'foo'],
                        (object) ['comment' => 'bar'],
                    ],
                ],
                (object) [
                    'comments' => [
                        (object) ['comment' => 'hello'],
                        (object) ['comment' => 'world'],
                    ],
                ],
            ],
        ], $data);
    }

    public function testHead()
    {
        $array = ['a', 'b', 'c'];
        $this->assertSame('a', head($array));
    }

    public function testLast()
    {
        $array = ['a', 'b', 'c'];
        $this->assertSame('c', last($array));
    }

    public function testClassUsesRecursiveShouldReturnTraitsOnParentClasses()
    {
        $this->assertSame(
            [
                SupportTestTraitTwo::class => SupportTestTraitTwo::class,
                SupportTestTraitOne::class => SupportTestTraitOne::class,
            ],
            class_uses_recursive(SupportTestClassTwo::class)
        );
    }

    public function testClassUsesRecursiveAcceptsObject()
    {
        $this->assertSame(
            [
                SupportTestTraitTwo::class => SupportTestTraitTwo::class,
                SupportTestTraitOne::class => SupportTestTraitOne::class,
            ],
            class_uses_recursive(new SupportTestClassTwo)
        );
    }

    public function testClassUsesRecursiveReturnParentTraitsFirst()
    {
        $this->assertSame(
            [
                SupportTestTraitTwo::class => SupportTestTraitTwo::class,
                SupportTestTraitOne::class => SupportTestTraitOne::class,
                SupportTestTraitThree::class => SupportTestTraitThree::class,
            ],
            class_uses_recursive(SupportTestClassThree::class)
        );
    }

    public function testTraitUsesRecursive()
    {
        $this->assertSame(
            [
                'Illuminate\Tests\Support\SupportTestTraitTwo' => 'Illuminate\Tests\Support\SupportTestTraitTwo',
                'Illuminate\Tests\Support\SupportTestTraitOne' => 'Illuminate\Tests\Support\SupportTestTraitOne',
            ],
            trait_uses_recursive(SupportTestClassOne::class)
        );

        $this->assertSame([], trait_uses_recursive(SupportTestClassTwo::class));
    }

    public function testStr()
    {
        $stringable = str('string-value');

        $this->assertInstanceOf(Stringable::class, $stringable);
        $this->assertSame('string-value', (string) $stringable);

        $stringable = str($name = null);
        $this->assertInstanceOf(Stringable::class, $stringable);
        $this->assertTrue($stringable->isEmpty());

        $strAccessor = str();
        $this->assertTrue((new ReflectionClass($strAccessor))->isAnonymous());
        $this->assertSame($strAccessor->limit('string-value', 3), 'str...');

        $strAccessor = str();
        $this->assertTrue((new ReflectionClass($strAccessor))->isAnonymous());
        $this->assertSame((string) $strAccessor, '');
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

    public function testThrow()
    {
        $this->expectException(LogicException::class);

        throw_if(true, new LogicException);
    }

    public function testThrowDefaultException()
    {
        $this->expectException(RuntimeException::class);

        throw_if(true);
    }

    public function testThrowExceptionWithMessage()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('test');

        throw_if(true, 'test');
    }

    public function testThrowExceptionAsStringWithMessage()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('test');

        throw_if(true, LogicException::class, 'test');
    }

    public function testThrowClosureException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('test');

        throw_if(true, fn () => new \Exception('test'));
    }

    public function testThrowClosureWithParamsException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('test');

        throw_if(true, fn (string $message) => new \Exception($message), 'test');
    }

    public function testThrowClosureStringWithParamsException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('test');

        throw_if(true, fn () => \Exception::class, 'test');
    }

    public function testThrowUnless()
    {
        $this->expectException(LogicException::class);

        throw_unless(false, new LogicException);
    }

    public function testThrowUnlessDefaultException()
    {
        $this->expectException(RuntimeException::class);

        throw_unless(false);
    }

    public function testThrowUnlessExceptionWithMessage()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('test');

        throw_unless(false, 'test');
    }

    public function testThrowUnlessExceptionAsStringWithMessage()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('test');

        throw_unless(false, LogicException::class, 'test');
    }

    public function testThrowReturnIfNotThrown()
    {
        $this->assertSame('foo', throw_unless('foo', new RuntimeException));
    }

    public function testThrowWithString()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Test Message');

        throw_if(true, RuntimeException::class, 'Test Message');
    }

    public function testOptional()
    {
        $this->assertNull(optional(null)->something());

        $this->assertEquals(10, optional(new class
        {
            public function something()
            {
                return 10;
            }
        })->something());
    }

    public function testOptionalWithCallback()
    {
        $this->assertNull(optional(null, function () {
            throw new RuntimeException(
                'The optional callback should not be called for null'
            );
        }));

        $this->assertEquals(10, optional(5, function ($number) {
            return $number * 2;
        }));
    }

    public function testOptionalWithArray()
    {
        $this->assertSame('here', optional(['present' => 'here'])['present']);
        $this->assertNull(optional(null)['missing']);
        $this->assertNull(optional(['present' => 'here'])->missing);
    }

    public function testOptionalReturnsObjectPropertyOrNull()
    {
        $this->assertSame('bar', optional((object) ['foo' => 'bar'])->foo);
        $this->assertNull(optional(['foo' => 'bar'])->foo);
        $this->assertNull(optional((object) ['foo' => 'bar'])->bar);
    }

    public function testOptionalDeterminesWhetherKeyIsSet()
    {
        $this->assertTrue(isset(optional(['foo' => 'bar'])['foo']));
        $this->assertFalse(isset(optional(['foo' => 'bar'])['bar']));
        $this->assertFalse(isset(optional()['bar']));
    }

    public function testOptionalAllowsToSetKey()
    {
        $optional = optional([]);
        $optional['foo'] = 'bar';
        $this->assertSame('bar', $optional['foo']);

        $optional = optional(null);
        $optional['foo'] = 'bar';
        $this->assertFalse(isset($optional['foo']));
    }

    public function testOptionalAllowToUnsetKey()
    {
        $optional = optional(['foo' => 'bar']);
        $this->assertTrue(isset($optional['foo']));
        unset($optional['foo']);
        $this->assertFalse(isset($optional['foo']));

        $optional = optional((object) ['foo' => 'bar']);
        $this->assertFalse(isset($optional['foo']));
        $optional['foo'] = 'bar';
        $this->assertFalse(isset($optional['foo']));
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

        $this->assertSame('$10.00', optional(new class
        {
            public function present()
            {
                return new class
                {
                    public function something()
                    {
                        return '$10.00';
                    }
                };
            }
        })->present()->something());
    }

    public function testRetry()
    {
        Sleep::fake();

        $attempts = retry(2, function ($attempts) {
            if ($attempts > 1) {
                return $attempts;
            }

            throw new RuntimeException;
        }, 100);

        // Make sure we made two attempts
        $this->assertEquals(2, $attempts);

        // Make sure we waited 100ms for the first attempt
        Sleep::assertSleptTimes(1);

        Sleep::assertSequence([
            Sleep::usleep(100_000),
        ]);
    }

    public function testRetryWithPassingSleepCallback()
    {
        Sleep::fake();

        $attempts = retry(3, function ($attempts) {
            if ($attempts > 2) {
                return $attempts;
            }

            throw new RuntimeException;
        }, function ($attempt, $exception) {
            $this->assertInstanceOf(RuntimeException::class, $exception);

            return $attempt * 100;
        });

        // Make sure we made three attempts
        $this->assertEquals(3, $attempts);

        // Make sure we waited 300ms for the first two attempts
        Sleep::assertSleptTimes(2);

        Sleep::assertSequence([
            Sleep::usleep(100_000),
            Sleep::usleep(200_000),
        ]);
    }

    public function testRetryWithPassingWhenCallback()
    {
        Sleep::fake();

        $attempts = retry(2, function ($attempts) {
            if ($attempts > 1) {
                return $attempts;
            }

            throw new RuntimeException;
        }, 100, function ($ex) {
            return true;
        });

        // Make sure we made two attempts
        $this->assertEquals(2, $attempts);

        // Make sure we waited 100ms for the first attempt
        Sleep::assertSleptTimes(1);

        Sleep::assertSequence([
            Sleep::usleep(100_000),
        ]);
    }

    public function testRetryWithFailingWhenCallback()
    {
        $this->expectException(RuntimeException::class);

        retry(2, function ($attempts) {
            if ($attempts > 1) {
                return $attempts;
            }

            throw new RuntimeException;
        }, 100, function ($ex) {
            return false;
        });
    }

    public function testRetryWithBackoff()
    {
        Sleep::fake();

        $attempts = retry([50, 100, 200], function ($attempts) {
            if ($attempts > 3) {
                return $attempts;
            }

            throw new RuntimeException;
        });

        // Make sure we made four attempts
        $this->assertEquals(4, $attempts);

        Sleep::assertSleptTimes(3);

        Sleep::assertSequence([
            Sleep::usleep(50_000),
            Sleep::usleep(100_000),
            Sleep::usleep(200_000),
        ]);
    }

    public function testRetryWithAThrowableBase()
    {
        Sleep::fake();

        $attempts = retry(2, function ($attempts) {
            if ($attempts > 1) {
                return $attempts;
            }

            throw new Error('This is an error');
        }, 100);

        // Make sure we made two attempts
        $this->assertEquals(2, $attempts);

        // Make sure we waited 100ms for the first attempt
        Sleep::assertSleptTimes(1);

        Sleep::assertSequence([
            Sleep::usleep(100_000),
        ]);
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

    public function testTransformDefaultWhenBlank()
    {
        $this->assertSame('baz', transform(null, function () {
            return 'bar';
        }, 'baz'));

        $this->assertSame('baz', transform('', function () {
            return 'bar';
        }, function () {
            return 'baz';
        }));
    }

    public function testWith()
    {
        $this->assertEquals(10, with(10));

        $this->assertEquals(10, with(5, function ($five) {
            return $five + 5;
        }));
    }

    public function testAppendConfig()
    {
        $this->assertSame([10000 => 'name', 10001 => 'family'], append_config([1 => 'name', 2 => 'family']));
        $this->assertSame([10000 => 'name', 10001 => 'family'], append_config(['name', 'family']));

        $array = ['name' => 'Taylor', 'family' => 'Otwell'];
        $this->assertSame($array, append_config($array));
    }

    public function testEnv()
    {
        $_SERVER['foo'] = 'bar';
        $this->assertSame('bar', env('foo'));
        $this->assertSame('bar', Env::get('foo'));
    }

    public function testEnvTrue()
    {
        $_SERVER['foo'] = 'true';
        $this->assertTrue(env('foo'));

        $_SERVER['foo'] = '(true)';
        $this->assertTrue(env('foo'));
    }

    public function testEnvFalse()
    {
        $_SERVER['foo'] = 'false';
        $this->assertFalse(env('foo'));

        $_SERVER['foo'] = '(false)';
        $this->assertFalse(env('foo'));
    }

    public function testEnvEmpty()
    {
        $_SERVER['foo'] = '';
        $this->assertSame('', env('foo'));

        $_SERVER['foo'] = 'empty';
        $this->assertSame('', env('foo'));

        $_SERVER['foo'] = '(empty)';
        $this->assertSame('', env('foo'));
    }

    public function testEnvNull()
    {
        $_SERVER['foo'] = 'null';
        $this->assertNull(env('foo'));

        $_SERVER['foo'] = '(null)';
        $this->assertNull(env('foo'));
    }

    public function testEnvDefault()
    {
        $_SERVER['foo'] = 'bar';
        $this->assertSame('bar', env('foo', 'default'));

        $_SERVER['foo'] = '';
        $this->assertSame('', env('foo', 'default'));

        unset($_SERVER['foo']);
        $this->assertSame('default', env('foo', 'default'));

        $_SERVER['foo'] = null;
        $this->assertSame('default', env('foo', 'default'));
    }

    public function testEnvEscapedString()
    {
        $_SERVER['foo'] = '"null"';
        $this->assertSame('null', env('foo'));

        $_SERVER['foo'] = "'null'";
        $this->assertSame('null', env('foo'));

        $_SERVER['foo'] = 'x"null"x'; // this should not be unquoted
        $this->assertSame('x"null"x', env('foo'));
    }

    public function testWriteArrayOfEnvVariablesToFile()
    {
        $filesystem = new Filesystem;
        $path = __DIR__.'/tmp/env-test-file';
        $filesystem->put($path, implode(PHP_EOL, [
            'APP_NAME=Laravel',
            'APP_ENV=local',
            'APP_KEY=base64:randomkey',
            'APP_DEBUG=true',
            'APP_URL=http://localhost',
            '',
            'DB_CONNECTION=mysql',
            'DB_HOST=',
        ]));

        Env::writeVariables([
            'APP_VIBE' => 'chill',
            'DB_HOST' => '127:0:0:1',
            'DB_PORT' => 3306,
            'BRAND_NEW_PREFIX' => 'fresh value',
        ], $path);

        $this->assertSame(
            implode(PHP_EOL, [
                'APP_NAME=Laravel',
                'APP_ENV=local',
                'APP_KEY=base64:randomkey',
                'APP_DEBUG=true',
                'APP_URL=http://localhost',
                'APP_VIBE=chill',
                '',
                'DB_CONNECTION=mysql',
                'DB_HOST="127:0:0:1"',
                'DB_PORT=3306',
                '',
                'BRAND_NEW_PREFIX="fresh value"',
            ]),
            $filesystem->get($path)
        );
    }

    public function testWriteArrayOfEnvVariablesToFileAndOverwrite()
    {
        $filesystem = new Filesystem;
        $path = __DIR__.'/tmp/env-test-file';
        $filesystem->put($path, implode(PHP_EOL, [
            'APP_NAME=Laravel',
            'APP_ENV=local',
            'APP_KEY=base64:randomkey',
            'APP_DEBUG=true',
            'APP_URL=http://localhost',
            '',
            'DB_CONNECTION=mysql',
            'DB_HOST=',
        ]));

        Env::writeVariables([
            'APP_VIBE' => 'chill',
            'DB_HOST' => '127:0:0:1',
            'DB_CONNECTION' => 'sqlite',
        ], $path, true);

        $this->assertSame(
            implode(PHP_EOL, [
                'APP_NAME=Laravel',
                'APP_ENV=local',
                'APP_KEY=base64:randomkey',
                'APP_DEBUG=true',
                'APP_URL=http://localhost',
                'APP_VIBE=chill',
                '',
                'DB_CONNECTION=sqlite',
                'DB_HOST="127:0:0:1"',
            ]),
            $filesystem->get($path)
        );
    }

    public function testWillNotOverwriteArrayOfVariables()
    {
        $filesystem = new Filesystem;
        $path = __DIR__.'/tmp/env-test-file';
        $filesystem->put($path, implode(PHP_EOL, [
            'APP_NAME=Laravel',
            'APP_ENV=local',
            'APP_KEY=base64:randomkey',
            'APP_DEBUG=true',
            'APP_URL=http://localhost',
            'APP_VIBE=odd',
            '',
            'DB_CONNECTION=mysql',
            'DB_HOST=',
        ]));

        Env::writeVariables([
            'APP_VIBE' => 'chill',
            'DB_HOST' => '127:0:0:1',
        ], $path);

        $this->assertSame(
            implode(PHP_EOL, [
                'APP_NAME=Laravel',
                'APP_ENV=local',
                'APP_KEY=base64:randomkey',
                'APP_DEBUG=true',
                'APP_URL=http://localhost',
                'APP_VIBE=odd',
                '',
                'DB_CONNECTION=mysql',
                'DB_HOST="127:0:0:1"',
            ]),
            $filesystem->get($path)
        );
    }

    public function testWriteVariableToFile()
    {
        $filesystem = new Filesystem;
        $path = __DIR__.'/tmp/env-test-file';
        $filesystem->put($path, implode(PHP_EOL, [
            'APP_NAME=Laravel',
            'APP_ENV=local',
            'APP_KEY=base64:randomkey',
            'APP_DEBUG=true',
            'APP_URL=http://localhost',
            '',
            'DB_CONNECTION=mysql',
            'DB_HOST=',
        ]));

        Env::writeVariable('APP_VIBE', 'chill', $path);

        $this->assertSame(
            implode(PHP_EOL, [
                'APP_NAME=Laravel',
                'APP_ENV=local',
                'APP_KEY=base64:randomkey',
                'APP_DEBUG=true',
                'APP_URL=http://localhost',
                'APP_VIBE=chill',
                '',
                'DB_CONNECTION=mysql',
                'DB_HOST=',
            ]),
            $filesystem->get($path)
        );
    }

    public function testWillNotOverwriteVariable()
    {
        $filesystem = new Filesystem;
        $path = __DIR__.'/tmp/env-test-file';
        $filesystem->put($path, implode(PHP_EOL, [
            'APP_NAME=Laravel',
            'APP_ENV=local',
            'APP_KEY=base64:randomkey',
            'APP_DEBUG=true',
            'APP_URL=http://localhost',
            'APP_VIBE=odd',
            '',
            'DB_CONNECTION=mysql',
            'DB_HOST=',
        ]));

        Env::writeVariable('APP_VIBE', 'chill', $path);

        $this->assertSame(
            implode(PHP_EOL, [
                'APP_NAME=Laravel',
                'APP_ENV=local',
                'APP_KEY=base64:randomkey',
                'APP_DEBUG=true',
                'APP_URL=http://localhost',
                'APP_VIBE=odd',
                '',
                'DB_CONNECTION=mysql',
                'DB_HOST=',
            ]),
            $filesystem->get($path)
        );
    }

    public function testWriteVariableToFileAndOverwrite()
    {
        $filesystem = new Filesystem;
        $path = __DIR__.'/tmp/env-test-file';
        $filesystem->put($path, implode(PHP_EOL, [
            'APP_NAME=Laravel',
            'APP_ENV=local',
            'APP_KEY=base64:randomkey',
            'APP_DEBUG=true',
            'APP_URL=http://localhost',
            'APP_VIBE=odd',
            '',
            'DB_CONNECTION=mysql',
            'DB_HOST=',
        ]));

        Env::writeVariable('APP_VIBE', 'chill', $path, true);

        $this->assertSame(
            implode(PHP_EOL, [
                'APP_NAME=Laravel',
                'APP_ENV=local',
                'APP_KEY=base64:randomkey',
                'APP_DEBUG=true',
                'APP_URL=http://localhost',
                'APP_VIBE=chill',
                '',
                'DB_CONNECTION=mysql',
                'DB_HOST=',
            ]),
            $filesystem->get($path)
        );
    }

    public function testWillThrowAnExceptionIfFileIsMissingWhenTryingToWriteVariables(): void
    {
        $this->expectExceptionObject(new RuntimeException('The file [missing-file] does not exist.'));

        Env::writeVariables([
            'APP_VIBE' => 'chill',
            'DB_HOST' => '127:0:0:1',
        ], 'missing-file');
    }

    public function testGetFromSERVERFirst()
    {
        $_ENV['foo'] = 'From $_ENV';
        $_SERVER['foo'] = 'From $_SERVER';
        $this->assertSame('From $_SERVER', env('foo'));
    }

    public function testRequiredEnvVariableThrowsAnExceptionWhenNotFound(): void
    {
        $this->expectExceptionObject(new RuntimeException('[required-does-not-exist] has no value'));

        Env::getOrFail('required-does-not-exist');
    }

    public function testRequiredEnvReturnsValue(): void
    {
        $_SERVER['required-exists'] = 'some-value';
        $this->assertSame('some-value', Env::getOrFail('required-exists'));
    }

    public function testLiteral(): void
    {
        $this->assertEquals(1, literal(1));
        $this->assertEquals('taylor', literal('taylor'));
        $this->assertEquals((object) ['name' => 'Taylor', 'role' => 'Developer'], literal(name: 'Taylor', role: 'Developer'));
    }

    public static function providesPregReplaceArrayData()
    {
        $pointerArray = ['Taylor', 'Otwell'];

        next($pointerArray);

        return [
            ['/:[a-z_]+/', ['8:30', '9:00'], 'The event will take place between :start and :end', 'The event will take place between 8:30 and 9:00'],
            ['/%s/', ['Taylor'], 'Hi, %s', 'Hi, Taylor'],
            ['/%s/', ['Taylor', 'Otwell'], 'Hi, %s %s', 'Hi, Taylor Otwell'],
            ['/%s/', [], 'Hi, %s %s', 'Hi,  '],
            ['/%s/', ['a', 'b', 'c'], 'Hi', 'Hi'],
            ['//', [], '', ''],
            ['/%s/', ['a'], '', ''],
            // non-sequential numeric keys → should still consume in natural order
            ['/%s/', [2 => 'A', 10 => 'B'], '%s %s', 'A B'],
            // associative keys → order should be insertion order, not keys/pointer
            ['/%s/', ['first' => 'A', 'second' => 'B'], '%s %s', 'A B'],
            // values that are "falsy" but must not be treated as empty by mistake, false->'' , null->''
            ['/%s/', ['0', 0, false, null], '%s|%s|%s|%s', '0|0||'],
            // The internal pointer of this array is not at the beginning
            ['/%s/', $pointerArray, 'Hi, %s %s', 'Hi, Taylor Otwell'],
        ];
    }

    #[DataProvider('providesPregReplaceArrayData')]
    public function testPregReplaceArray($pattern, $replacements, $subject, $expectedOutput)
    {
        $this->assertSame(
            $expectedOutput,
            preg_replace_array($pattern, $replacements, $subject)
        );
    }
}

trait SupportTestTraitOne
{
    //
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
    //
}

trait SupportTestTraitThree
{
    //
}

class SupportTestClassThree extends SupportTestClassTwo
{
    use SupportTestTraitThree;
}

trait SupportTestTraitArrayAccess
{
    public function __construct(protected array $items = [])
    {
    }

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset ?? '', $this->items);
    }

    public function offsetGet($offset): mixed
    {
        return $this->items[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        $this->items[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
    }
}

trait SupportTestTraitArrayIterable
{
    public function __construct(protected array $items = [])
    {
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}

class SupportTestArrayAccess implements ArrayAccess
{
    use SupportTestTraitArrayAccess;
}

class SupportTestArrayIterable implements IteratorAggregate
{
    use SupportTestTraitArrayIterable;
}

class SupportTestArrayAccessIterable implements ArrayAccess, IteratorAggregate
{
    use SupportTestTraitArrayAccess, SupportTestTraitArrayIterable {
        SupportTestTraitArrayAccess::__construct insteadof SupportTestTraitArrayIterable;
    }
}

class SupportTestCountable implements Countable
{
    public function count(): int
    {
        return 0;
    }
}
