<?php

namespace Illuminate\Tests\Testing\Constraints;

use Illuminate\Support\LazyCollection;
use Illuminate\Testing\Constraints\ArraySubset;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

class ArraySubsetTest extends TestCase
{
    public static function iterableProvider()
    {
        yield 'plain array' => [['foo', 'bar']];
        yield 'collection' => [collect(['foo', 'bar'])];
        yield 'lazy collection' => [new LazyCollection(['foo', 'bar'])];
    }

    /**
     * @test
     *
     * @dataProvider iterableProvider
     */
    public function it_should_validate_a_subset_of_an_array(iterable $iterable)
    {
        $constraint = new ArraySubset($iterable);

        $this->assertTrue($constraint->evaluate(['foo', 'bar', 'baz'], returnResult: true));
    }

    /**
     * @test
     *
     * @dataProvider iterableProvider
     */
    public function it_will_fail_should_the_array_not_be_a_subset(iterable $iterable)
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage(<<<'MSG'
            Failed asserting that an array has the subset Array &0 (
                0 => 'foo'
                1 => 'bar'
            ).
            MSG
        );

        $constraint = new ArraySubset($iterable);
        $constraint->evaluate(['foo']);

    }
}
