<?php

namespace Illuminate\Tests\Support;

use PHPUnit\Framework\TestCase;
use Illuminate\Support\Traits\ForwardsCalls;

class ForwardsCallsTest extends TestCase
{
    public function testForwardsCalls()
    {
        $results = (new ForwardsCallsOne)->forwardedTwo('foo', 'bar');

        $this->assertEquals(['foo', 'bar'], $results);
    }

    public function testNestedForwardCalls()
    {
        $results = (new ForwardsCallsOne)->forwardedBase('foo', 'bar');

        $this->assertEquals(['foo', 'bar'], $results);
    }

    /**
     * @expectedException  \BadMethodCallException
     * @expectedExceptionMessage  Call to undefined method Illuminate\Tests\Support\ForwardsCallsOne::missingMethod()
     */
    public function testMissingForwardedCallThrowsCorrectError()
    {
        (new ForwardsCallsOne)->missingMethod('foo', 'bar');
    }

    /**
     * @expectedException  \BadMethodCallException
     * @expectedExceptionMessage  Call to undefined method Illuminate\Tests\Support\ForwardsCallsOne::this1_shouldWork_too()
     */
    public function testMissingAlphanumericForwardedCallThrowsCorrectError()
    {
        (new ForwardsCallsOne)->this1_shouldWork_too('foo', 'bar');
    }

    /**
     * @expectedException  \Error
     * @expectedExceptionMessage  Call to undefined method Illuminate\Tests\Support\ForwardsCallsBase::missingMethod()
     */
    public function testNonForwardedErrorIsNotTamperedWith()
    {
        (new ForwardsCallsOne)->baseError('foo', 'bar');
    }

    /**
     * @expectedException  \BadMethodCallException
     * @expectedExceptionMessage  Call to undefined method Illuminate\Tests\Support\ForwardsCallsOne::test()
     */
    public function testThrowBadMethodCallException()
    {
        (new ForwardsCallsOne)->throwTestException('test');
    }
}

class ForwardsCallsOne
{
    use ForwardsCalls;

    public function __call($method, $parameters)
    {
        return $this->forwardCallTo(new ForwardsCallsTwo, $method, $parameters);
    }

    public function throwTestException($method)
    {
        static::throwBadMethodCallException($method);
    }
}

class ForwardsCallsTwo
{
    use ForwardsCalls;

    public function __call($method, $parameters)
    {
        return $this->forwardCallTo(new ForwardsCallsBase, $method, $parameters);
    }

    public function forwardedTwo(...$parameters)
    {
        return $parameters;
    }
}

class ForwardsCallsBase
{
    public function forwardedBase(...$parameters)
    {
        return $parameters;
    }

    public function baseError()
    {
        return $this->missingMethod();
    }
}
