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

    public function testItOnlyCallableAndBooleanAreAcceptableArgumentsOfTheRule()
    {
        $rule = new NullableIf(false);

        $rule = new NullableIf(true);

        $this->expectException(InvalidArgumentException::class);

        $rule = new NullableIf('phpinfo');
    }

    public function testItReturnedRuleIsNotSerializable()
    {
        $this->expectException(Exception::class);

        $rule = serialize(new NullableIf(function () {
            return true;
        }));
    }

    public function testNullableIfValidationMessage()
    {
        $validator = Validator::make(
            ['field' => 'value'],
            ['field' => ['nullable_if:other,1']]
        );

        $this->assertTrue($validator->fails());
        $this->assertEquals(
            'The field field must be nullable when other is 1.',
            $validator->errors()->first('field')
        );
    }
}
