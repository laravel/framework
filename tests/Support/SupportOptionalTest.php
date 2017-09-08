<?php

namespace Illuminate\Tests\Support;

use PHPUnit\Framework\TestCase;
use Illuminate\Support\Optional;

class SupportOptionalTest extends TestCase
{
    /**
     * @param mixed $value
     * @dataProvider nullableValueProvider
     */
    public function testOf($value)
    {
        $this->assertEquals(new Optional($value), Optional::of($value));
    }

    public function nullableValueProvider()
    {
        return [
            [null],
            [5],
            [new \stdClass()],
        ];
    }

    /**
     * @param mixed $value
     * @dataProvider someValueProvider
     */
    public function testSome($value)
    {
        $this->assertEquals(new Optional($value), Optional::some($value));
    }

    public function someValueProvider()
    {
        return [
            [5],
            [new \stdClass()],
        ];
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Null value passed to some() method. Use none() instead.
     */
    public function testSomeThrowsExceptionWhenNullGiven()
    {
        Optional::some(null);
    }

    public function testNone()
    {
        $this->assertEquals(new Optional(null), Optional::none());
    }
}
