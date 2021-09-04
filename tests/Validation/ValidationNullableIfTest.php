<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Validation\Rules\NullableIf;
use PHPUnit\Framework\TestCase;

class ValidationNullableIfTest extends TestCase
{
    public function testItCorrectlyFormatsAStringVersionOfTheRule()
    {
        $rule = new NullableIf(function () {
            return true;
        });

        $this->assertSame('nullable', (string) $rule);

        $rule = new NullableIf(function () {
            return false;
        });

        $this->assertSame('', (string) $rule);

        $rule = new NullableIf(true);

        $this->assertSame('nullable', (string) $rule);

        $rule = new NullableIf(false);

        $this->assertSame('', (string) $rule);
    }
}
