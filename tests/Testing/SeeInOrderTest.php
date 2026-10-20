<?php

namespace Illuminate\Tests\Testing;

use Illuminate\Testing\Constraints\SeeInOrder;
use PHPUnit\Framework\TestCase;

class SeeInOrderTest extends TestCase
{
    public function testStringZeroIsNotTreatedAsEmpty()
    {
        $constraint = new SeeInOrder('Count 0 items');

        $this->assertTrue($constraint->matches(['Count', '0', 'items']));
        $this->assertFalse($constraint->matches(['Count', '0', 'missing']));
    }

    public function testNullAndEmptyStringAreStillSkipped()
    {
        $constraint = new SeeInOrder('hello world');

        $this->assertTrue($constraint->matches([null, '', 'hello', 'world']));
    }
}
