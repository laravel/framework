<?php

namespace Illuminate\Tests\Validation;

use PHPUnit\Framework\TestCase;
use Illuminate\Validation\Rules\Pwned;

class ValidationPwnedRuleTest extends TestCase
{
    public function testItCorrectlyFormatsAStringVersionOfTheRule()
    {
        $rule = new Pwned();

        $this->assertEquals('pwned:threshold=1', (string) $rule);

        $rule = new Pwned(3);

        $this->assertEquals('pwned:threshold=3', (string) $rule);

        $rule = new Pwned(3, true);

        $this->assertEquals('pwned:threshold=3,skipOnError', (string) $rule);
    }
}
