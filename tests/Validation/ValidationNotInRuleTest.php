<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\NotIn;
use PHPUnit\Framework\TestCase;

class ValidationNotInRuleTest extends TestCase
{
    public function testItCorrectlyFormatsAStringVersionOfTheRule()
    {
        $rule = new NotIn(['Laravel', 'Framework', 'PHP']);

        $this->assertSame('not_in:"Laravel","Framework","PHP"', (string) $rule);

        $rule = Rule::notIn([1, 2, 3, 4]);

        $this->assertSame('not_in:"1","2","3","4"', (string) $rule);

        $rule = Rule::notIn(collect([1, 2, 3, 4]));

        $this->assertSame('not_in:"1","2","3","4"', (string) $rule);

        $rule = Rule::notIn('1', '2', '3', '4');

        $this->assertSame('not_in:"1","2","3","4"', (string) $rule);
    }
}
