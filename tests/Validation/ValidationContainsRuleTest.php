<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Contains;
use PHPUnit\Framework\TestCase;

class ValidationContainsRuleTest extends TestCase
{
    public function testItCorrectlyFormatsAStringVersionOfTheRule()
    {
        $rule = new Contains(['Laravel', 'Framework', 'PHP']);

        $this->assertSame('contains_with:"Laravel","Framework","PHP"', (string) $rule);

        $rule = Rule::contains([1, 2, 3, 4]);

        $this->assertSame('contains_with:"1","2","3","4"', (string) $rule);

        $rule = Rule::contains(collect([1, 2, 3, 4]));

        $this->assertSame('contains_with:"1","2","3","4"', (string) $rule);

        $rule = Rule::contains('1', '2', '3', '4');

        $this->assertSame('contains_with:"1","2","3","4"', (string) $rule);
    }
}
