<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Validation\Rules\SometimesIf;
use PHPUnit\Framework\TestCase;

class ValidationSometimesIfTest extends TestCase
{
    public function testItClousureReturnsFormatsAStringVersionOfTheRule()
    {
        $rule = new SometimesIf(function () {
            return true;
        });

        $this->assertSame('sometimes', (string) $rule);

        $rule = new SometimesIf(function () {
            return false;
        });

        $this->assertSame('', (string) $rule);

        $rule = new SometimesIf(true);

        $this->assertSame('sometimes', (string) $rule);

        $rule = new SometimesIf(false);

        $this->assertSame('', (string) $rule);
    }
}
