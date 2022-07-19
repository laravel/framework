<?php

namespace Illuminate\Tests\Validation;

use Exception;
use Illuminate\Validation\Rules\ProhibitedUnless;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;

class ValidationProhibitedUnlessTest extends TestCase
{
    public function testItClousureReturnsFormatsAStringVersionOfTheRule()
    {
        $rule = new ProhibitedUnless(function () {
            return true;
        });

        $this->assertSame('', (string) $rule);

        $rule = new ProhibitedUnless(function () {
            return false;
        });

        $this->assertSame('prohibited', (string) $rule);

        $rule = new ProhibitedUnless(true);

        $this->assertSame('', (string) $rule);

        $rule = new ProhibitedUnless(false);

        $this->assertSame('prohibited', (string) $rule);
    }

    public function testItOnlyCallableAndBooleanAreAcceptableArgumentsOfTheRule()
    {
        $rule = new ProhibitedUnless(false);

        $rule = new ProhibitedUnless(true);

        foreach ([1, 1.1, 'phpinfo', new stdClass] as $condition) {
            try {
                $rule = new ProhibitedUnless($condition);
                $this->fail('The ProhibitedUnless constructor must not accept '.gettype($condition));
            } catch (InvalidArgumentException $exception) {
                $this->assertEquals('The provided condition must be a callable or boolean.', $exception->getMessage());
            }
        }
    }

    public function testItReturnedRuleIsNotSerializable()
    {
        $this->expectException(Exception::class);

        $rule = serialize(new ProhibitedUnless(function () {
            return true;
        }));
    }
}
