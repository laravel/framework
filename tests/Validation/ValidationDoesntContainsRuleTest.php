<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\DoesntContains;
use PHPUnit\Framework\TestCase;

class ValidationDoesntContainsRuleTest extends TestCase
{
    public function testItCorrectlyFormatsAStringVersionOfTheRule()
    {
        $rule = new DoesntContains(['Laravel', 'Framework', 'PHP']);

        $this->assertSame('doesnt_contains_with:"Laravel","Framework","PHP"', (string) $rule);

        $rule = Rule::doesntContains([1, 2, 3, 4]);

        $this->assertSame('doesnt_contains_with:"1","2","3","4"', (string) $rule);

        $rule = Rule::doesntContains(collect([1, 2, 3, 4]));

        $this->assertSame('doesnt_contains_with:"1","2","3","4"', (string) $rule);

        $rule = Rule::doesntContains('1', '2', '3', '4');

        $this->assertSame('doesnt_contains_with:"1","2","3","4"', (string) $rule);
    }
}
