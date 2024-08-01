<?php

namespace Illuminate\Tests\Validation;

use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\NullableIf;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ValidationNullableIfTest extends TestCase
{
    public function testItClosureReturnsFormatsAStringVersionOfTheRule()
    {
        $rule = new NullableIf(function () {
            return true;
        });

        $this->assertSame('nullable', (string) $rule);

        $rule = new NullableIf(function () {
            return false;
        });

        $this->assertSame('', (string) $rule);

        $rule = new NullableIf(true);

        $this->assertSame('nullable', (string) $rule);

        $rule = new NullableIf(false);

        $this->assertSame('', (string) $rule);
    }

    public function testItReturnedRuleIsNotSerializable()
    {
        $this->expectException(Exception::class);

        $rule = serialize(new NullableIf(function () {
            return true;
        }));
    }
}
