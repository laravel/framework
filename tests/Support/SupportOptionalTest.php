<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Optional;
use PHPUnit\Framework\TestCase;
use stdClass;

class SupportOptionalTest extends TestCase
{
    public function testGetExistItemOnObject()
    {
        $expected = 'test';

        $targetObj = new stdClass;
        $targetObj->item = $expected;

        $optional = new Optional($targetObj);

        $this->assertEquals($expected, $optional->item);
    }

    public function testGetNotExistItemOnObject()
    {
        $targetObj = new stdClass;

        $optional = new Optional($targetObj);

        $this->assertNull($optional->item);
    }

    public function testIssetExistItemOnObject()
    {
        $targetObj = new stdClass;
        $targetObj->item = '';

        $optional = new Optional($targetObj);

        $this->assertTrue(isset($optional->item));
    }

    public function testIssetNotExistItemOnObject()
    {
        $targetObj = new stdClass;

        $optional = new Optional($targetObj);

        $this->assertFalse(isset($optional->item));
    }

    public function testGetExistItemOnArray()
    {
        $expected = 'test';

        $targetArr = [
            'item' => $expected,
        ];

        $optional = new Optional($targetArr);

        $this->assertEquals($expected, $optional['item']);
    }

    public function testGetNotExistItemOnArray()
    {
        $targetObj = [];

        $optional = new Optional($targetObj);

        $this->assertNull($optional['item']);
    }

    public function testIssetExistItemOnArray()
    {
        $targetArr = [
            'item' => '',
        ];

        $optional = new Optional($targetArr);

        $this->assertArrayHasKey('item', $optional);
        $this->assertTrue(isset($optional->item));
    }

    public function testIssetNotExistItemOnArray()
    {
        $targetArr = [];

        $optional = new Optional($targetArr);

        $this->assertArrayNotHasKey('item', $optional);
        $this->assertFalse(isset($optional->item));
    }

    public function testIssetExistItemOnNull()
    {
        $targetNull = null;

        $optional = new Optional($targetNull);

        $this->assertFalse(isset($optional->item));
    }
}
