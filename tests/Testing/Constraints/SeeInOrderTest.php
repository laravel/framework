<?php

namespace Illuminate\Tests\Testing\Constraints;

use Illuminate\Testing\Constraints\SeeInOrder;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

class SeeInOrderTest extends TestCase
{

    /**
     * @test
     */
    public function it_validates_elements_are_in_order()
    {
        $constraint = new SeeInOrder('foo bar baz');

        $this->assertTrue($constraint->evaluate(['foo', 'bar', 'baz'], returnResult: true));
    }

    /**
     * @test
     */
    public function it_will_fail_should_the_string_be_in_an_invalid_order()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Failed asserting that \'foo bar baz\' contains "bar" in specified order.');

        $constraint = new SeeInOrder('foo bar baz');
        $constraint->evaluate(['foo', 'baz', 'bar']);
    }

}
