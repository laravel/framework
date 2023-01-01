<?php

namespace Illuminate\Tests\Config;

use Illuminate\Config\Repository;
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
    }

    public function testPush()
    {
        $this->assertSame('aaa', $this->repository->get('array.0'));
        $this->assertSame('zzz', $this->repository->get('array.1'));
        $this->repository->push('array', 'xxx');
        $this->assertSame('aaa', $this->repository->get('array.0'));
        $this->assertSame('zzz', $this->repository->get('array.1'));
        $this->assertSame('xxx', $this->repository->get('array.2'));
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
        $this->assertTrue(isset($this->repository['foo']));
        $this->assertFalse(isset($this->repository['not-exist']));
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
    }

    public function testOffsetUnset()
    {
        $this->assertArrayHasKey('associate', $this->repository->all());
        $this->assertSame($this->config['associate'], $this->repository->get('associate'));

        unset($this->repository['associate']);

        $this->assertArrayHasKey('associate', $this->repository->all());
        $this->assertNull($this->repository->get('associate'));
    }

    public function testsItIsMacroable()
    {
        $this->repository->macro('foo', function () {
            return 'macroable';
        });

        $this->assertSame('macroable', $this->repository->foo());
    }
}
