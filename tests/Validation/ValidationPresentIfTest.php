<?php

namespace Illuminate\Tests\Validation;

use Exception;
use Illuminate\Validation\Rules\PresentIf;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;

class ValidationPresentIfTest extends TestCase
{
    public function testItFormatsAStringVersionOfTheRule()
    {
        $rule = new PresentIf(function () {
            return true;
        });

        $this->assertSame('present', (string) $rule);

        $rule = new PresentIf(function () {
            return false;
        });

        $this->assertSame('', (string) $rule);

        $rule = new PresentIf(true);

        $this->assertSame('present', (string) $rule);

        $rule = new PresentIf(false);

        $this->assertSame('', (string) $rule);
    }

    public function testItOnlyAcceptsBooleanAndCallableAsArguments()
    {
        $rule = new PresentIf(false);

        $rule = new PresentIf(true);

        foreach ([1, 1.1, 'phpinfo', new stdClass] as $condition) {
            try {
                $rule = new PresentIf($condition);
                $this->fail('The ProhibitedIf constructor must not accept '.gettype($condition));
            } catch (InvalidArgumentException $exception) {
                $this->assertEquals('The provided condition must be a callable or boolean.', $exception->getMessage());
            }
        }
    }

    public function testItIsNotSerializable()
    {
        $this->expectException(Exception::class);

        $rule = serialize(new PresentIf(function () {
            return true;
        }));
    }
}
