<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Base64;
use PHPUnit\Framework\TestCase;

class ValidationBase64Test extends TestCase
{
    public function testBase64StringPassValidation()
    {
        $string = base64_encode('Hello world');

        $rule = new Base64();

        $this->assertTrue($rule->passes('', $string));
    }

    public function testNonBase64StringDoNotPassValidation()
    {
        $string = base64_encode('Hello world').'-';

        $rule = new Base64();

        $this->assertFalse($rule->passes('', $string));
    }
}
