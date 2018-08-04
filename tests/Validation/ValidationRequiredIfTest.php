<?php

namespace Illuminate\Tests\Validation;

use PHPUnit\Framework\TestCase;
use Illuminate\Validation\Rules\RequiredIf;

class ValidationRequiredIfTest extends TestCase
{
    public function testItClousureReturnsFormatsAStringVersionOfTheRule()
    {
        $rule = new RequiredIf(function () {
            return true;
        });

        $this->assertEquals('required', (string) $rule);

        $rule = new RequiredIf(function () {
            return false;
        });

        $this->assertEquals('', (string) $rule);

        $rule = new RequiredIf(true);

        $this->assertEquals('required', (string) $rule);

        $rule = new RequiredIf(false);

        $this->assertEquals('', (string) $rule);
    }
}
