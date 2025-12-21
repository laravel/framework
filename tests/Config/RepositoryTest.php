<?php

namespace Illuminate\Tests\Config;

use Illuminate\Config\Repository;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase
{
    /**
     * @var \Illuminate\Config\Repository
     */
    protected $repository;

    /**
     * @var array
     */
    protected $config;

    protected function setUp(): void
    {
        $this->repository = new Repository($this->config = [
            'foo' => 'bar',
            'bar' => 'baz',
            'baz' => 'bat',
            'null' => null,
            'boolean' => true,
            'integer' => 1,
            'float' => 1.1,
            'associate' => [
                'x' => 'xxx',
                'y' => 'yyy',
            ],
            'array' => [
                'aaa',
                'zzz',
            ],
            'x' => [
                'z' => 'zoo',
            ],
            'a.b' => 'c',
            'a' => [
                'b.c' => 'd',
            ],
        ]);

        parent::setUp();
    }

    public function testGetValueWhenKeyContainDot()
    {
        $this->assertSame(
            $this->repository->get('a.b'), 'c'
        );
        $this->assertNull(
            $this->repository->get('a.b.c')
        );

        $this->assertNull($this->repository->get('x.y.z'));
        $this->assertNull($this->repository->get('.'));
    }

    public function testGetBooleanValue()
    {
        $this->assertTrue(
            $this->repository->get('boolean')
        );
    }

    public function testGetNullValue()
    {
        $this->assertNull(
            $this->repository->get('null')
        );
    }

    public function testConstruct()
    {
        $this->assertInstanceOf(Repository::class, $this->repository);
    }

    public function testHasIsTrue()
    {
        $this->assertTrue($this->repository->has('foo'));
    }

    public function testHasIsFalse()
    {
        $this->assertFalse($this->repository->has('not-exist'));
    }

    public function testGet()
    {
        $this->assertSame('bar', $this->repository->get('foo'));
    }

    public function testGetWithArrayOfKeys()
    {
        $this->assertSame([
            'foo' => 'bar',
            'bar' => 'baz',
            'none' => null,
        ], $this->repository->get([
            'foo',
            'bar',
            'none',
        ]));

        $this->assertSame([
            'x.y' => 'default',
            'x.z' => 'zoo',
            'bar' => 'baz',
            'baz' => 'bat',
        ], $this->repository->get([
            'x.y' => 'default',
            'x.z' => 'default',
            'bar' => 'default',
            'baz',
        ]));
    }

    public function testGetMany()
    {
        $this->assertSame([
            'foo' => 'bar',
            'bar' => 'baz',
            'none' => null,
        ], $this->repository->getMany([
            'foo',
            'bar',
            'none',
        ]));

        $this->assertSame([
            'x.y' => 'default',
            'x.z' => 'zoo',
            'bar' => 'baz',
            'baz' => 'bat',
        ], $this->repository->getMany([
            'x.y' => 'default',
            'x.z' => 'default',
            'bar' => 'default',
            'baz',
        ]));
    }

    public function testGetWithDefault()
    {
        $this->assertSame('default', $this->repository->get('not-exist', 'default'));
    }

    public function testSet()
    {
        $this->repository->set('key', 'value');
        $this->assertSame('value', $this->repository->get('key'));
    }

    public function testSetArray()
    {
        $this->repository->set([
            'key1' => 'value1',
            'key2' => 'value2',
            'key3',
            'key4' => [
                'foo' => 'bar',
                'bar' => [
                    'foo' => 'bar',
                ],
            ],
        ]);
        $this->assertSame('value1', $this->repository->get('key1'));
        $this->assertSame('value2', $this->repository->get('key2'));
        $this->assertNull($this->repository->get('key3'));
        $this->assertSame('bar', $this->repository->get('key4.foo'));
        $this->assertSame('bar', $this->repository->get('key4.bar.foo'));
        $this->assertNull($this->repository->get('key5'));
    }

    public function testPrepend()
    {
        $this->assertSame('aaa', $this->repository->get('array.0'));
        $this->assertSame('zzz', $this->repository->get('array.1'));

        $this->repository->prepend('array', 'xxx');
        $this->assertSame('xxx', $this->repository->get('array.0'));
        $this->assertSame('aaa', $this->repository->get('array.1'));
        $this->assertSame('zzz', $this->repository->get('array.2'));
        $this->assertNull($this->repository->get('array.3'));
        $this->assertCount(3, $this->repository->get('array'));
    }

    public function testPush()
    {
        $this->assertSame('aaa', $this->repository->get('array.0'));
        $this->assertSame('zzz', $this->repository->get('array.1'));
        $this->repository->push('array', 'xxx');
        $this->assertSame('aaa', $this->repository->get('array.0'));
        $this->assertSame('zzz', $this->repository->get('array.1'));
        $this->assertSame('xxx', $this->repository->get('array.2'));

        $this->assertCount(3, $this->repository->get('array'));
    }

    public function testPrependWithNewKey()
    {
        $this->repository->prepend('new_key', 'xxx');
        $this->assertSame(['xxx'], $this->repository->get('new_key'));
    }

    public function testPushWithNewKey()
    {
        $this->repository->push('new_key', 'xxx');
        $this->assertSame(['xxx'], $this->repository->get('new_key'));
    }

    public function testAll()
    {
        $this->assertSame($this->config, $this->repository->all());
    }

    public function testOffsetExists()
    {
        $data = [
            'foo' => 'bar',
            'null_value' => null,
            'empty_string' => '',
            'numeric_value' => 123,
        ];
        $this->repository->set($data);

        $this->assertTrue(isset($this->repository['foo']));
        $this->assertFalse(isset($this->repository['not-exist']));
        $this->assertTrue(isset($this->repository['null_value']));
        $this->assertTrue(isset($this->repository['empty_string']));
        $this->assertTrue(isset($this->repository['numeric_value']));
        $this->assertFalse(isset($this->repository[-1]));
        $this->assertFalse(isset($this->repository['non_numeric']));
    }

    public function testOffsetGet()
    {
        $this->assertNull($this->repository['not-exist']);
        $this->assertSame('bar', $this->repository['foo']);
        $this->assertSame([
            'x' => 'xxx',
            'y' => 'yyy',
        ], $this->repository['associate']);
    }

    public function testOffsetSet()
    {
        $this->assertNull($this->repository['key']);

        $this->repository['key'] = 'value';

        $this->assertSame('value', $this->repository['key']);

        $this->repository['key'] = 'new_value';
        $this->assertSame('new_value', $this->repository['key']);

        $this->repository['new_key'] = null;
        $this->assertNull($this->repository['new_key']);

        $this->repository[''] = 'value';
        $this->assertSame('value', $this->repository['']);

        $this->repository[123] = '123';
        $this->assertSame('123', $this->repository[123]);
    }

    public function testOffsetUnset()
    {
        $this->assertArrayHasKey('associate', $this->repository->all());
        $this->assertSame($this->config['associate'], $this->repository->get('associate'));

        unset($this->repository['associate']);

        $this->assertArrayHasKey('associate', $this->repository->all());
        $this->assertNull($this->repository->get('associate'));
    }

    public function testItIsMacroable()
    {
        $this->repository->macro('foo', function () {
            return 'macroable';
        });

        $this->assertSame('macroable', $this->repository->foo());
    }

    public function testItGetsAsString(): void
    {
        $this->assertSame(
            'c', $this->repository->string('a.b')
        );
    }

    public function testItThrowsAnExceptionWhenTryingToGetNonStringValueAsString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('#^Configuration value for key \[a\] must be a string, (.*) given.#');

        $this->repository->string('a');
    }

    public function testItGetsAsArray(): void
    {
        $this->assertSame(
            $this->repository->array('array'), ['aaa', 'zzz']
        );
    }

    public function testItThrowsAnExceptionWhenTryingToGetNonArrayValueAsArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('#Configuration value for key \[a.b\] must be an array, (.*) given.#');

        $this->repository->array('a.b');
    }

    public function testItGetsAsCollection(): void
    {
        $collection = $this->repository->collection('array');

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertSame(['aaa', 'zzz'], $collection->toArray());
    }

    public function testItGetsAsBoolean(): void
    {
        $this->assertTrue(
            $this->repository->boolean('boolean')
        );
    }

    public function testItThrowsAnExceptionWhenTryingToGetNonBooleanValueAsBoolean(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('#Configuration value for key \[a.b\] must be a boolean, (.*) given.#');

        $this->repository->boolean('a.b');
    }

    public function testItGetsAsInteger(): void
    {
        $this->assertSame(
            $this->repository->integer('integer'), 1
        );
    }

    public function testItThrowsAnExceptionWhenTryingToGetNonIntegerValueAsInteger(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('#Configuration value for key \[a.b\] must be an integer, (.*) given.#');

        $this->repository->integer('a.b');
    }

    public function testItGetsAsFloat(): void
    {
        $this->assertSame(
            $this->repository->float('float'), 1.1
        );
    }

    public function testItThrowsAnExceptionWhenTryingToGetNonFloatValueAsFloat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('#^Configuration value for key \[a.b\] must be a float, (.*) given.#');

        $this->repository->float('a.b');
    }
}
