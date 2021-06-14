<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Validation\Rules\RequiredIf;
use PHPUnit\Framework\TestCase;

class ValidationRequiredIfTest extends TestCase
{
    public function testItClousureReturnsFormatsAStringVersionOfTheRule()
    {
        $rule = new RequiredIf(function () {
            return true;
        });

        $this->assertSame('required', (string) $rule);

        $rule = new RequiredIf(function () {
            return false;
        });

        $this->assertSame('', (string) $rule);

        $rule = new RequiredIf(true);

        $this->assertSame('required', (string) $rule);

        $rule = new RequiredIf(false);

        $this->assertSame('', (string) $rule);
    }

    public function testItOnlyCallableAndBooleanAreAcceptableArgumentsOfTheRule()
    {
        $rule = new RequiredIf(false);

        $rule = new RequiredIf(true);

        $this->expectException(InvalidArgumentException::class);

        $rule = new RequiredIf('phpinfo');

        $rule = new RequiredIf(12.3);

        $rule = new RequiredIf(new stdClass());
    }

    public function testItReturnedRuleIsNotSerializable()
    {
        $this->expectException(Exception::class);

        $rule = serialize(new RequiredIf(function () {
            return true;
        }));

        $rule = serialize(new RequiredIf());
    }
}
