<?php

namespace Illuminate\Tests\Validation;

use Exception;
use Illuminate\Validation\Rules\AcceptedIf;
use Illuminate\Validation\Rules\DeclinedIf;
use Illuminate\Validation\Rules\RequiredIf;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ValidationDeclinedIfTest extends TestCase
{
    public function testItClosureReturnsFormatsAStringVersionOfTheRule()
    {
        $rule = new DeclinedIf(function () {
            return true;
        });

        $this->assertSame('declined', (string) $rule);

        $rule = new DeclinedIf(function () {
            return false;
        });

        $this->assertSame('', (string) $rule);

        $rule = new DeclinedIf(true);

        $this->assertSame('declined', (string) $rule);

        $rule = new DeclinedIf(false);

        $this->assertSame('', (string) $rule);
    }

    public function testItOnlyCallableAndBooleanAreAcceptableArgumentsOfTheRule()
    {
        $rule = new DeclinedIf(false);

        $rule = new DeclinedIf(true);

        $this->expectException(InvalidArgumentException::class);

        $rule = new DeclinedIf('phpinfo');
    }

    public function testItReturnedRuleIsNotSerializable()
    {
        $this->expectException(Exception::class);

        $rule = serialize(new DeclinedIf(function () {
            return true;
        }));
    }
}
