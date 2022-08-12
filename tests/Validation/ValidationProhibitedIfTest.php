<?php

namespace Illuminate\Tests\Validation;

use Exception;
use Illuminate\Validation\Rules\ProhibitedIf;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;

class ValidationProhibitedIfTest extends TestCase
{
    public function testItClousureReturnsFormatsAStringVersionOfTheRule()
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

        foreach ([1, 1.1, 'phpinfo', new stdClass] as $condition) {
            try {
                $rule = new ProhibitedIf($condition);
                $this->fail('The ProhibitedIf constructor must not accept '.gettype($condition));
            } catch (InvalidArgumentException $exception) {
                $this->assertEquals('The provided condition must be a callable or boolean.', $exception->getMessage());
            }
        }
    }

    public function testItReturnedRuleIsNotSerializable()
    {
        $this->expectException(Exception::class);

        $rule = serialize(new ProhibitedIf(function () {
            return true;
        }));
    }
}
