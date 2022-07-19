<?php

namespace Illuminate\Tests\Validation;

use Exception;
use Illuminate\Validation\Rules\RequiredUnless;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ValidationRequiredUnlessTest extends TestCase
{
    public function testItClousureReturnsFormatsAStringVersionOfTheRule()
    {
        $rule = new RequiredUnless(function () {
            return true;
        });

        $this->assertSame('', (string) $rule);

        $rule = new RequiredUnless(function () {
            return false;
        });

        $this->assertSame('required', (string) $rule);

        $rule = new RequiredUnless(true);

        $this->assertSame('', (string) $rule);

        $rule = new RequiredUnless(false);

        $this->assertSame('required', (string) $rule);
    }

    public function testItOnlyCallableAndBooleanAreAcceptableArgumentsOfTheRule()
    {
        $rule = new RequiredUnless(false);

        $rule = new RequiredUnless(true);

        $this->expectException(InvalidArgumentException::class);

        $rule = new RequiredUnless('phpinfo');
    }

    public function testItReturnedRuleIsNotSerializable()
    {
        $this->expectException(Exception::class);

        $rule = serialize(new RequiredUnless(function () {
            return true;
        }));
    }
}
