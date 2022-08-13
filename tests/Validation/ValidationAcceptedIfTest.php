<?php

namespace Illuminate\Tests\Validation;

use Exception;
use Illuminate\Validation\Rules\AcceptedIf;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ValidationAcceptedIfTest extends TestCase
{
    public function testItClosureReturnsFormatsAStringVersionOfTheRule()
    {
        $rule = new AcceptedIf(function () {
            return true;
        });

        $this->assertSame('accepted', (string) $rule);

        $rule = new AcceptedIf(function () {
            return false;
        });

        $this->assertSame('', (string) $rule);

        $rule = new AcceptedIf(true);

        $this->assertSame('accepted', (string) $rule);

        $rule = new AcceptedIf(false);

        $this->assertSame('', (string) $rule);
    }

    public function testItOnlyCallableAndBooleanAreAcceptableArgumentsOfTheRule()
    {
        $rule = new AcceptedIf(false);

        $rule = new AcceptedIf(true);

        $this->expectException(InvalidArgumentException::class);

        $rule = new AcceptedIf('phpinfo');
    }

    public function testItReturnedRuleIsNotSerializable()
    {
        $this->expectException(Exception::class);

        $rule = serialize(new AcceptedIf(function () {
            return true;
        }));
    }
}
