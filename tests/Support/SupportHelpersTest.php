<?php

namespace Illuminate\Tests\Support;

use Laravel;
use stdClass;
use ArrayAccess;
use Mockery as m;
use RuntimeException;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\Optional;
use Illuminate\Contracts\Support\Htmlable;

class SupportHelpersTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testE()
    {
        $str = 'A \'quote\' is <b>bold</b>';
        $this->assertEquals('A &#039;quote&#039; is &lt;b&gt;bold&lt;/b&gt;', Laravel::e($str));
        $html = m::mock(Htmlable::class);
        $html->shouldReceive('toHtml')->andReturn($str);
        $this->assertEquals($str, Laravel::e($html));
    }

    public function testClassBasename()
    {
        $this->assertEquals('Baz', Laravel::classBasename('Foo\Bar\Baz'));
        $this->assertEquals('Baz', Laravel::classBasename('Baz'));
    }

    public function testValue()
    {
        $this->assertEquals('foo', Laravel::value('foo'));
        $this->assertEquals('foo', Laravel::value(function () {
            return 'foo';
        }));
    }

    public function testObjectGet()
    {
        $class = new stdClass;
        $class->name = new stdClass;
        $class->name->first = 'Taylor';

        $this->assertEquals('Taylor', Laravel::objectGet($class, 'name.first'));
    }

    public function testDataGet()
    {
        $object = (object) ['users' => ['name' => ['Taylor', 'Otwell']]];
        $array = [(object) ['users' => [(object) ['name' => 'Taylor']]]];
        $dottedArray = ['users' => ['first.name' => 'Taylor', 'middle.name' => null]];
        $arrayAccess = new SupportTestArrayAccess(['price' => 56, 'user' => new SupportTestArrayAccess(['name' => 'John']), 'email' => null]);

        $this->assertEquals('Taylor', Laravel::dataGet($object, 'users.name.0'));
        $this->assertEquals('Taylor', Laravel::dataGet($array, '0.users.0.name'));
        $this->assertNull(Laravel::dataGet($array, '0.users.3'));
        $this->assertEquals('Not found', Laravel::dataGet($array, '0.users.3', 'Not found'));
        $this->assertEquals('Not found', Laravel::dataGet($array, '0.users.3', function () {
            return 'Not found';
        }));
        $this->assertEquals('Taylor', Laravel::dataGet($dottedArray, ['users', 'first.name']));
        $this->assertNull(Laravel::dataGet($dottedArray, ['users', 'middle.name']));
        $this->assertEquals('Not found', Laravel::dataGet($dottedArray, ['users', 'last.name'], 'Not found'));
        $this->assertEquals(56, Laravel::dataGet($arrayAccess, 'price'));
        $this->assertEquals('John', Laravel::dataGet($arrayAccess, 'user.name'));
        $this->assertEquals('void', Laravel::dataGet($arrayAccess, 'foo', 'void'));
        $this->assertEquals('void', Laravel::dataGet($arrayAccess, 'user.foo', 'void'));
        $this->assertNull(Laravel::dataGet($arrayAccess, 'foo'));
        $this->assertNull(Laravel::dataGet($arrayAccess, 'user.foo'));
        $this->assertNull(Laravel::dataGet($arrayAccess, 'email', 'Not found'));
    }

    public function testDataGetWithNestedArrays()
    {
        $array = [
            ['name' => 'taylor', 'email' => 'taylorotwell@gmail.com'],
            ['name' => 'abigail'],
            ['name' => 'dayle'],
        ];

        $this->assertEquals(['taylor', 'abigail', 'dayle'], Laravel::dataGet($array, '*.name'));
        $this->assertEquals(['taylorotwell@gmail.com', null, null], Laravel::dataGet($array, '*.email', 'irrelevant'));

        $array = [
            'users' => [
                ['first' => 'taylor', 'last' => 'otwell', 'email' => 'taylorotwell@gmail.com'],
                ['first' => 'abigail', 'last' => 'otwell'],
                ['first' => 'dayle', 'last' => 'rees'],
            ],
            'posts' => null,
        ];

        $this->assertEquals(['taylor', 'abigail', 'dayle'], Laravel::dataGet($array, 'users.*.first'));
        $this->assertEquals(['taylorotwell@gmail.com', null, null], Laravel::dataGet($array, 'users.*.email', 'irrelevant'));
        $this->assertEquals('not found', Laravel::dataGet($array, 'posts.*.date', 'not found'));
        $this->assertNull(Laravel::dataGet($array, 'posts.*.date'));
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

        $this->assertEquals(['taylor', 'abigail', 'abigail', 'dayle', 'dayle', 'taylor'], Laravel::dataGet($array, 'posts.*.comments.*.author'));
        $this->assertEquals([4, 3, 2, null, null, 1], Laravel::dataGet($array, 'posts.*.comments.*.likes'));
        $this->assertEquals([], Laravel::dataGet($array, 'posts.*.users.*.name', 'irrelevant'));
        $this->assertEquals([], Laravel::dataGet($array, 'posts.*.users.*.name'));
    }

    public function testDataFill()
    {
        $data = ['foo' => 'bar'];

        $this->assertEquals(['foo' => 'bar', 'baz' => 'boom'], Laravel::dataFill($data, 'baz', 'boom'));
        $this->assertEquals(['foo' => 'bar', 'baz' => 'boom'], Laravel::dataFill($data, 'baz', 'noop'));
        $this->assertEquals(['foo' => [], 'baz' => 'boom'], Laravel::dataFill($data, 'foo.*', 'noop'));
        $this->assertEquals(
            ['foo' => ['bar' => 'kaboom'], 'baz' => 'boom'],
            Laravel::dataFill($data, 'foo.bar', 'kaboom')
        );
    }

    public function testDataFillWithStar()
    {
        $data = ['foo' => 'bar'];

        $this->assertEquals(
            ['foo' => []],
            Laravel::dataFill($data, 'foo.*.bar', 'noop')
        );

        $this->assertEquals(
            ['foo' => [], 'bar' => [['baz' => 'original'], []]],
            Laravel::dataFill($data, 'bar', [['baz' => 'original'], []])
        );

        $this->assertEquals(
            ['foo' => [], 'bar' => [['baz' => 'original'], ['baz' => 'boom']]],
            Laravel::dataFill($data, 'bar.*.baz', 'boom')
        );

        $this->assertEquals(
            ['foo' => [], 'bar' => [['baz' => 'original'], ['baz' => 'boom']]],
            Laravel::dataFill($data, 'bar.*', 'noop')
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

        Laravel::dataFill($data, 'posts.*.comments.*.name', 'Filled');

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
            Laravel::dataSet($data, 'baz', 'boom')
        );

        $this->assertEquals(
            ['foo' => 'bar', 'baz' => 'kaboom'],
            Laravel::dataSet($data, 'baz', 'kaboom')
        );

        $this->assertEquals(
            ['foo' => [], 'baz' => 'kaboom'],
            Laravel::dataSet($data, 'foo.*', 'noop')
        );

        $this->assertEquals(
            ['foo' => ['bar' => 'boom'], 'baz' => 'kaboom'],
            Laravel::dataSet($data, 'foo.bar', 'boom')
        );

        $this->assertEquals(
            ['foo' => ['bar' => 'boom'], 'baz' => ['bar' => 'boom']],
            Laravel::dataSet($data, 'baz.bar', 'boom')
        );

        $this->assertEquals(
            ['foo' => ['bar' => 'boom'], 'baz' => ['bar' => ['boom' => ['kaboom' => 'boom']]]],
            Laravel::dataSet($data, 'baz.bar.boom.kaboom', 'boom')
        );
    }

    public function testDataSetWithStar()
    {
        $data = ['foo' => 'bar'];

        $this->assertEquals(
            ['foo' => []],
            Laravel::dataSet($data, 'foo.*.bar', 'noop')
        );

        $this->assertEquals(
            ['foo' => [], 'bar' => [['baz' => 'original'], []]],
            Laravel::dataSet($data, 'bar', [['baz' => 'original'], []])
        );

        $this->assertEquals(
            ['foo' => [], 'bar' => [['baz' => 'boom'], ['baz' => 'boom']]],
            Laravel::dataSet($data, 'bar.*.baz', 'boom')
        );

        $this->assertEquals(
            ['foo' => [], 'bar' => ['overwritten', 'overwritten']],
            Laravel::dataSet($data, 'bar.*', 'overwritten')
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

        Laravel::dataSet($data, 'posts.*.comments.*.name', 'Filled');

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
        $this->assertEquals('a', Laravel::head($array));
    }

    public function testLast()
    {
        $array = ['a', 'b', 'c'];
        $this->assertEquals('c', Laravel::last($array));
    }

    public function testClassUsesRecursiveShouldReturnTraitsOnParentClasses()
    {
        $this->assertSame([
            SupportTestTraitTwo::class => SupportTestTraitTwo::class,
            SupportTestTraitOne::class => SupportTestTraitOne::class,
        ],
        Laravel::classUsesRecursive(SupportTestClassTwo::class));
    }

    public function testClassUsesRecursiveAcceptsObject()
    {
        $this->assertSame([
            SupportTestTraitTwo::class => SupportTestTraitTwo::class,
            SupportTestTraitOne::class => SupportTestTraitOne::class,
        ],
        Laravel::classUsesRecursive(new SupportTestClassTwo));
    }

    public function testClassUsesRecursiveReturnParentTraitsFirst()
    {
        $this->assertSame([
            SupportTestTraitTwo::class => SupportTestTraitTwo::class,
            SupportTestTraitOne::class => SupportTestTraitOne::class,
            SupportTestTraitThree::class => SupportTestTraitThree::class,
        ],
        Laravel::classUsesRecursive(SupportTestClassThree::class));
    }

    public function testTap()
    {
        $object = (object) ['id' => 1];
        $this->assertEquals(2, Laravel::tap($object, function ($object) {
            $object->id = 2;
        })->id);

        $mock = m::mock();
        $mock->shouldReceive('foo')->once()->andReturn('bar');
        $this->assertEquals($mock, Laravel::tap($mock)->foo());
    }

    public function testThrow()
    {
        $this->expectException(RuntimeException::class);

        Laravel::throwIf(true, new RuntimeException);
    }

    public function testThrowReturnIfNotThrown()
    {
        $this->assertSame('foo', Laravel::throwUnless('foo', new RuntimeException));
    }

    public function testThrowWithString()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Test Message');

        Laravel::throwIf(true, RuntimeException::class, 'Test Message');
    }

    public function testOptional()
    {
        $this->assertNull(Laravel::optional(null)->something());

        $this->assertEquals(10, Laravel::optional(new class {
            public function something()
            {
                return 10;
            }
        })->something());
    }

    public function testOptionalWithCallback()
    {
        $this->assertNull(Laravel::optional(null, function () {
            throw new RuntimeException(
                'The optional callback should not be called for null'
            );
        }));

        $this->assertEquals(10, Laravel::optional(5, function ($number) {
            return $number * 2;
        }));
    }

    public function testOptionalWithArray()
    {
        $this->assertEquals('here', Laravel::optional(['present' => 'here'])['present']);
        $this->assertNull(Laravel::optional(null)['missing']);
        $this->assertNull(Laravel::optional(['present' => 'here'])->missing);
    }

    public function testOptionalReturnsObjectPropertyOrNull()
    {
        $this->assertSame('bar', Laravel::optional((object) ['foo' => 'bar'])->foo);
        $this->assertNull(Laravel::optional(['foo' => 'bar'])->foo);
        $this->assertNull(Laravel::optional((object) ['foo' => 'bar'])->bar);
    }

    public function testOptionalDeterminesWhetherKeyIsSet()
    {
        $this->assertTrue(isset(Laravel::optional(['foo' => 'bar'])['foo']));
        $this->assertFalse(isset(Laravel::optional(['foo' => 'bar'])['bar']));
        $this->assertFalse(isset(Laravel::optional()['bar']));
    }

    public function testOptionalAllowsToSetKey()
    {
        $optional = Laravel::optional([]);
        $optional['foo'] = 'bar';
        $this->assertSame('bar', $optional['foo']);

        $optional = Laravel::optional(null);
        $optional['foo'] = 'bar';
        $this->assertFalse(isset($optional['foo']));
    }

    public function testOptionalAllowToUnsetKey()
    {
        $optional = Laravel::optional(['foo' => 'bar']);
        $this->assertTrue(isset($optional['foo']));
        unset($optional['foo']);
        $this->assertFalse(isset($optional['foo']));

        $optional = Laravel::optional((object) ['foo' => 'bar']);
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

        $this->assertNull(Laravel::optional(null)->present()->something());

        $this->assertEquals('$10.00', Laravel::optional(new class {
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

    public function testRetry()
    {
        $startTime = microtime(true);

        $attempts = Laravel::retry(2, function ($attempts) {
            if ($attempts > 1) {
                return $attempts;
            }

            throw new RuntimeException;
        }, 100);

        // Make sure we made two attempts
        $this->assertEquals(2, $attempts);

        // Make sure we waited 100ms for the first attempt
        $this->assertTrue(microtime(true) - $startTime >= 0.1);
    }

    public function testTransform()
    {
        $this->assertEquals(10, Laravel::transform(5, function ($value) {
            return $value * 2;
        }));

        $this->assertNull(Laravel::transform(null, function () {
            return 10;
        }));
    }

    public function testTransformDefaultWhenBlank()
    {
        $this->assertEquals('baz', Laravel::transform(null, function () {
            return 'bar';
        }, 'baz'));

        $this->assertEquals('baz', Laravel::transform('', function () {
            return 'bar';
        }, function () {
            return 'baz';
        }));
    }

    public function testWith()
    {
        $this->assertEquals(10, Laravel::with(10));

        $this->assertEquals(10, Laravel::with(5, function ($five) {
            return $five + 5;
        }));
    }

    public function testEnv()
    {
        $_SERVER['foo'] = 'bar';
        $this->assertSame('bar', Laravel::env('foo'));
    }

    public function testEnvTrue()
    {
        $_SERVER['foo'] = 'true';
        $this->assertTrue(Laravel::env('foo'));

        $_SERVER['foo'] = '(true)';
        $this->assertTrue(Laravel::env('foo'));
    }

    public function testEnvFalse()
    {
        $_SERVER['foo'] = 'false';
        $this->assertFalse(Laravel::env('foo'));

        $_SERVER['foo'] = '(false)';
        $this->assertFalse(Laravel::env('foo'));
    }

    public function testEnvEmpty()
    {
        $_SERVER['foo'] = '';
        $this->assertSame('', Laravel::env('foo'));

        $_SERVER['foo'] = 'empty';
        $this->assertSame('', Laravel::env('foo'));

        $_SERVER['foo'] = '(empty)';
        $this->assertSame('', Laravel::env('foo'));
    }

    public function testEnvNull()
    {
        $_SERVER['foo'] = 'null';
        $this->assertNull(Laravel::env('foo'));

        $_SERVER['foo'] = '(null)';
        $this->assertNull(Laravel::env('foo'));
    }

    public function testEnvDefault()
    {
        $_SERVER['foo'] = 'bar';
        $this->assertEquals('bar', Laravel::env('foo', 'default'));

        $_SERVER['foo'] = '';
        $this->assertEquals('', Laravel::env('foo', 'default'));

        unset($_SERVER['foo']);
        $this->assertEquals('default', Laravel::env('foo', 'default'));

        $_SERVER['foo'] = null;
        $this->assertEquals('default', Laravel::env('foo', 'default'));
    }

    public function testEnvEscapedString()
    {
        $_SERVER['foo'] = '"null"';
        $this->assertSame('null', Laravel::env('foo'));

        $_SERVER['foo'] = "'null'";
        $this->assertSame('null', Laravel::env('foo'));

        $_SERVER['foo'] = 'x"null"x'; // this should not be unquoted
        $this->assertSame('x"null"x', Laravel::env('foo'));
    }

    public function testGetFromENVFirst()
    {
        $_ENV['foo'] = 'From $_ENV';
        $_SERVER['foo'] = 'From $_SERVER';
        $this->assertSame('From $_ENV', Laravel::env('foo'));
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
