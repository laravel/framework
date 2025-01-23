<?php

namespace Illuminate\Tests\Container;

use Illuminate\Container\Util;
use PHPUnit\Framework\TestCase;
use ReflectionParameter;
use stdClass;

class UtilTest extends TestCase
{
    public function testUnwrapIfClosure()
    {
        $this->assertSame('foo', Util::unwrapIfClosure('foo'));
        $this->assertSame('foo', Util::unwrapIfClosure(function () {
            return 'foo';
        }));
    }

    public function testArrayWrap()
    {
        $string = 'a';
        $array = ['a'];
        $object = new stdClass;
        $object->value = 'a';
        $this->assertEquals(['a'], Util::arrayWrap($string));
        $this->assertEquals($array, Util::arrayWrap($array));
        $this->assertEquals([$object], Util::arrayWrap($object));
        $this->assertEquals([], Util::arrayWrap(null));
        $this->assertEquals([null], Util::arrayWrap([null]));
        $this->assertEquals([null, null], Util::arrayWrap([null, null]));
        $this->assertEquals([''], Util::arrayWrap(''));
        $this->assertEquals([''], Util::arrayWrap(['']));
        $this->assertEquals([false], Util::arrayWrap(false));
        $this->assertEquals([false], Util::arrayWrap([false]));
        $this->assertEquals([0], Util::arrayWrap(0));

        $obj = new stdClass;
        $obj->value = 'a';
        $obj = unserialize(serialize($obj));
        $this->assertEquals([$obj], Util::arrayWrap($obj));
        $this->assertSame($obj, Util::arrayWrap($obj)[0]);
    }

    public function testGetParameterClassName()
    {
        $parameter = new ReflectionParameter(function (stdClass $foo) {
        }, 0);
        $this->assertSame('stdClass', Util::getParameterClassName($parameter));

        $parameter = new ReflectionParameter(function (string $foo) {
        }, 0);
        $this->assertNull(Util::getParameterClassName($parameter));
    }
}
