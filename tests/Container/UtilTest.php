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
        $this->assertSame(['a'], Util::arrayWrap($string));
        $this->assertSame($array, Util::arrayWrap($array));
        $this->assertSame([$object], Util::arrayWrap($object));
        $this->assertSame([], Util::arrayWrap(null));
        $this->assertSame([null], Util::arrayWrap([null]));
        $this->assertSame([null, null], Util::arrayWrap([null, null]));
        $this->assertSame([''], Util::arrayWrap(''));
        $this->assertSame([''], Util::arrayWrap(['']));
        $this->assertSame([false], Util::arrayWrap(false));
        $this->assertSame([false], Util::arrayWrap([false]));
        $this->assertSame([0], Util::arrayWrap(0));

        $obj = new stdClass;
        $obj->value = 'a';
        $obj = unserialize(serialize($obj));
        $this->assertSame([$obj], Util::arrayWrap($obj));
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
