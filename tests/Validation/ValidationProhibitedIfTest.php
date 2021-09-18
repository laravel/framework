<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Validation\Rules\ProhibitedIf;
use PHPUnit\Framework\TestCase;

class ValidationProhibitedIfTest extends TestCase
{
    public function testItClosureReturnsFormatsAStringVersionOfTheRule()
    {
        $rule = new ProhibitedIf(function () {
            return true;
        });

        $this->assertSame('prohibited', (string) $rule);

        $rule = new ProhibitedIf(function () {
            return false;
        });

        $this->assertSame('', (string) $rule);

        $rule = new ProhibitedIf(true);

        $this->assertSame('prohibited', (string) $rule);

        $rule = new ProhibitedIf(false);

        $this->assertSame('', (string) $rule);
    }

    public function testItOnlyCallableAndBooleanAreAcceptableArgumentsOfTheRule()
    {
        $rule = new ProhibitedIf(false);

        $rule = new ProhibitedIf(true);

        $this->expectException(\InvalidArgumentException::class);

        $rule = new ProhibitedIf('phpinfo');
    }

    public function testItReturnedRuleIsNotSerializable()
    {
        $this->expectException(\Exception::class);

        $rule = serialize(new ProhibitedIf(function () {
            return true;
        }));
    }
}
