<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Validation\Rule;
use PHPUnit\Framework\TestCase;

class ValidationMacroTest extends TestCase
{
    public function testMacroable()
    {
        // Define a phone validation macro
        Rule::macro('phone', function () {
            return 'regex:/^([0-9\s\-\+\(\)]*)$/';
        });

        $actualRule = Rule::phone();
        $this->assertSame('regex:/^([0-9\s\-\+\(\)]*)$/', $actualRule);
    }

    public function testMacroArguments()
    {
        Rule::macro('maxLength', function (int $length) {
            return "max:{$length}";
        });

        $actualRule = Rule::maxLength(10);
        $this->assertSame('max:10', $actualRule);
    }

    public function testMacroDefaultArguments()
    {
        Rule::macro('maxLength', function ($length = 255) {
            return "max:{$length}";
        });

        $actualRule = Rule::maxLength();  // No argument provided, should use default value
        $this->assertSame('max:255', $actualRule);
    }
}
