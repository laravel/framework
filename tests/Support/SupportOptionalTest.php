<?php

namespace Illuminate\Tests\Support;

use PHPUnit\Framework\TestCase;
use Illuminate\Support\Optional;

class SupportOptionalTest extends TestCase
{
    public function testGetExistItemOnObject()
    {
        $expected = 'test';

        $targetObj = new \stdClass;
        $targetObj->item = $expected;

        $optional = new Optional($targetObj);

        $this->assertEquals($expected, $optional->item);
    }

    public function testGetNotExistItemOnObject()
    {
        $targetObj = new \stdClass;

        $optional = new Optional($targetObj);

        $this->assertNull($optional->item);
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
}
