<?php

namespace Illuminate\Tests\Validation;

use Exception;
use Illuminate\Validation\Rules\ExcludeIf;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;

class ValidationExcludeIfTest extends TestCase
{
    public function testItReturnsStringVersionOfRuleWhenCast()
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

    public function testItValidatesCallableAndBooleanAreAcceptableArguments()
    {
        new ExcludeIf(false);
        new ExcludeIf(true);
        new ExcludeIf(fn () => true);

        foreach ([1, 1.1, 'phpinfo', new stdClass] as $condition) {
            try {
                new ExcludeIf($condition);
                $this->fail('The ExcludeIf constructor must not accept '.gettype($condition));
            } catch (InvalidArgumentException $exception) {
                $this->assertEquals('The provided condition must be a callable or boolean.', $exception->getMessage());
            }
        }
    }

    public function testItThrowsExceptionIfRuleIsNotSerializable()
    {
        $this->expectException(Exception::class);

        serialize(new ExcludeIf(function () {
            return true;
        }));
    }
}
