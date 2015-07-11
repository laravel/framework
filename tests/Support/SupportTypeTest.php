<?php

use Illuminate\Support\Type;
use Illuminate\Support\Collection;

class SupportTypeTest extends PHPUnit_Framework_TestCase
{
    public function testCastInteger()
    {
        $this->assertInternalType('integer', Type::castInteger('123'));
    }

    public function testCastInt()
    {
        $this->assertInternalType('integer', Type::castInt('12.3'));
    }

    public function testCastFloat()
    {
        $this->assertInternalType('float', Type::castFloat('1.23'));
    }

    public function testCastReal()
    {
        $this->assertInternalType('float', Type::castFloat('12.3'));
    }

    public function testCastDouble()
    {
        $this->assertInternalType('float', Type::castFloat('123'));
    }

    public function testCastString()
    {
        $this->assertInternalType('string', Type::castString(123));
    }

    public function testCastBoolean()
    {
        $this->assertInternalType('boolean', Type::castBoolean('1'));
    }

    public function testCastBool()
    {
        $this->assertInternalType('boolean', Type::castBoolean(''));
    }

    public function testCastObject()
    {
        $this->assertInstanceOf(stdClass::class, Type::castObject('{"test":"test"}'));
    }

    public function testCastArray()
    {
        $this->assertInternalType('array', Type::castArray('{"test":"test"}'));
    }

    public function testCastJson()
    {
        $this->assertInternalType('array', Type::castJson('{"test":"test"}'));
    }

    public function testCastCollection()
    {
        $this->assertInstanceOf(Collection::class, Type::castCollection('[{"test":"test"}]'));
    }
}
