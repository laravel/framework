<?php

namespace Illuminate\Tests\Support;

use ArrayAccess;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Env;
use Illuminate\Support\Optional;
use LogicException;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

class SupportHelpersTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testE()
    {
        $str = 'A \'quote\' is <b>bold</b>';
        $this->assertSame('A &#039;quote&#039; is &lt;b&gt;bold&lt;/b&gt;', e($str));
        $html = m::mock(Htmlable::class);
        $html->shouldReceive('toHtml')->andReturn($str);
        $this->assertEquals($str, e($html));
    }

    public function testClassBasename()
    {
        $this->assertSame('Baz', class_basename('Foo\Bar\Baz'));
        $this->assertSame('Baz', class_basename('Baz'));
    }

    public function testValue()
    {
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
        $this->assertSame([
            SupportTestTraitTwo::class => SupportTestTraitTwo::class,
            SupportTestTraitOne::class => SupportTestTraitOne::class,
        ],
        class_uses_recursive(SupportTestClassTwo::class));
    }

    public function testClassUsesRecursiveAcceptsObject()
    {
        $this->assertSame([
            SupportTestTraitTwo::class => SupportTestTraitTwo::class,
            SupportTestTraitOne::class => SupportTestTraitOne::class,
        ],
        class_uses_recursive(new SupportTestClassTwo));
    }

    public function testClassUsesRecursiveReturnParentTraitsFirst()
    {
        $this->assertSame([
            SupportTestTraitTwo::class => SupportTestTraitTwo::class,
            SupportTestTraitOne::class => SupportTestTraitOne::class,
            SupportTestTraitThree::class => SupportTestTraitThree::class,
        ],
        class_uses_recursive(SupportTestClassThree::class));
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
        $startTime = microtime(true);

        $attempts = retry(2, function ($attempts) {
            if ($attempts > 1) {
                return $attempts;
            }

            throw new RuntimeException;
        }, 100);

        // Make sure we made two attempts
        $this->assertEquals(2, $attempts);

        // Make sure we waited 100ms for the first attempt
        $this->assertEqualsWithDelta(0.1, microtime(true) - $startTime, 0.02);
    }

    public function testRetryWithPassingSleepCallback()
    {
        $startTime = microtime(true);

        $attempts = retry(3, function ($attempts) {
            if ($attempts > 2) {
                return $attempts;
            }

            throw new RuntimeException;
        }, function ($attempt) {
            return $attempt * 100;
        });

        // Make sure we made three attempts
        $this->assertEquals(3, $attempts);

        // Make sure we waited 300ms for the first two attempts
        $this->assertEqualsWithDelta(0.3, microtime(true) - $startTime, 0.02);
    }

    public function testRetryWithPassingWhenCallback()
    {
        $startTime = microtime(true);

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
        $this->assertEqualsWithDelta(0.1, microtime(true) - $startTime, 0.02);
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

    public function testGetFromSERVERFirst()
    {
        $_ENV['foo'] = 'From $_ENV';
        $_SERVER['foo'] = 'From $_SERVER';
        $this->assertSame('From $_SERVER', env('foo'));
    }

    public function providesPregReplaceArrayData()
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
            // The internal pointer of this array is not at the beginning
            ['/%s/', $pointerArray, 'Hi, %s %s', 'Hi, Taylor Otwell'],
        ];
    }

    /**
     * @dataProvider providesPregReplaceArrayData
     */
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

class SupportTestArrayAccess implements ArrayAccess
{
    protected $attributes = [];

    public function __construct($attributes = [])
    {
        $this->attributes = $attributes;
    }

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->attributes);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->attributes[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        $this->attributes[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->attributes[$offset]);
    }
}
