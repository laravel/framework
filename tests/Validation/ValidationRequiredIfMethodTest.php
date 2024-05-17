<?php

namespace Illuminate\Tests\Validation;

use Exception;
use Illuminate\Validation\Rules\RequiredIfMethod;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ValidationRequiredIfMethodTest extends TestCase
{
    public function testStringRepresentationOfRule()
    {
        $rule = new RequiredIfMethod('POST');
        $this->assertSame('required_if_method:POST', (string) $rule);

        $rule = new RequiredIfMethod('GET');
        $this->assertSame('required_if_method:GET', (string) $rule);
    }

    public function testRuleAcceptsValidHttpMethods()
    {
        new RequiredIfMethod('POST');
        new RequiredIfMethod('GET');
        new RequiredIfMethod('PUT');
        new RequiredIfMethod('PATCH');
        new RequiredIfMethod('DELETE');

        $this->assertTrue(true);
    }

    public function testRuleThrowsExceptionForInvalidHttpMethod()
    {
        $this->expectException(InvalidArgumentException::class);
        new RequiredIfMethod('INVALID_METHOD');
    }

    public function testRuleIsNotSerializable()
    {
        $this->expectException(Exception::class);
        serialize(new RequiredIfMethod('POST'));
    }
}
