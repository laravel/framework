<?php

namespace Illuminate\Tests\Validation;

use Exception;
use Illuminate\Validation\Rules\ExcludeIf;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ValidationExcludeIfTest extends TestCase
{
    public function testItClousureReturnsFormatsAStringVersionOfTheRule()
    {
        $rule = new ExcludeIf(function () {
            return true;
        });

        $this->assertSame('exclude', (string) $rule);

        $rule = new ExcludeIf(function () {
            return false;
        });

        $this->assertSame('', (string) $rule);

        $rule = new ExcludeIf(true);

        $this->assertSame('exclude', (string) $rule);

        $rule = new ExcludeIf(false);

        $this->assertSame('', (string) $rule);
    }

    public function testItOnlyCallableAndBooleanAreAcceptableArgumentsOfTheRule()
    {
        $rule = new ExcludeIf(false);

        $rule = new ExcludeIf(true);

        $this->expectException(InvalidArgumentException::class);

        $rule = new ExcludeIf('phpinfo');
    }

    public function testItReturnedRuleIsNotSerializable()
    {
        $this->expectException(Exception::class);

        $rule = serialize(new ExcludeIf(function () {
            return true;
        }));
    }
}
