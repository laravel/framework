<?php

namespace Illuminate\Tests\Testing;

use Illuminate\Testing\Constraints\SeeInOrder;
use PHPUnit\Framework\TestCase;

class SeeInOrderTest extends TestCase
{
    public function testMatchesZero()
    {
        $this->assertTrue((new SeeInOrder('0'))->matches(['0']));
        $this->assertFalse((new SeeInOrder('1'))->matches(['0']));
    }

    public function testMatchesZeroInOrder()
    {
        $values = ['before', '0', 'after'];

        $this->assertTrue((new SeeInOrder('before 0 after'))->matches($values));
        $this->assertFalse((new SeeInOrder('before after'))->matches($values));
        $this->assertFalse((new SeeInOrder('0 before after'))->matches($values));
    }

    public function testSkipsEmptyStringsAndNull()
    {
        $this->assertTrue((new SeeInOrder('Hello World'))->matches(['', null, 'Hello', '', 'World', null]));
    }
}
