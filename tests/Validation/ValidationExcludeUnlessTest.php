<?php

namespace Illuminate\Tests\Validation;

use Exception;
use Illuminate\Validation\Rules\ExcludeUnless;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;

class ValidationExcludeUnlessTest extends TestCase
{
    public function testItClousureReturnsFormatsAStringVersionOfTheRule()
    {
        $rule = new ExcludeUnless(function () {
            return true;
        });

        $this->assertSame('', (string) $rule);

        $rule = new ExcludeUnless(function () {
            return false;
        });

        $this->assertSame('exclude', (string) $rule);

        $rule = new ExcludeUnless(true);

        $this->assertSame('', (string) $rule);

        $rule = new ExcludeUnless(false);

        $this->assertSame('exclude', (string) $rule);
    }

    public function testItOnlyCallableAndBooleanAreAcceptableArgumentsOfTheRule()
    {
        new ExcludeUnless(false);
        new ExcludeUnless(true);
        new ExcludeUnless(fn () => true);

        foreach ([1, 1.1, 'phpinfo', new stdClass] as $condition) {
            try {
                new ExcludeUnless($condition);
                $this->fail('The ExcludeUnless constructor must not accept '.gettype($condition));
            } catch (InvalidArgumentException $exception) {
                $this->assertEquals('The provided condition must be a callable or boolean.', $exception->getMessage());
            }
        }
    }

    public function testItReturnedRuleIsNotSerializable()
    {
        $this->expectException(Exception::class);

        $rule = serialize(new ExcludeUnless(function () {
            return true;
        }));
    }
}
