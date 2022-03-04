<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Tests\Validation\fixtures\Values;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\InsensitiveIn;
use PHPUnit\Framework\TestCase;

class ValidationInsensitiveInRuleTest extends TestCase
{
    public function testItCorrectlyFormatsAStringVersionOfTheRule()
    {
        $rule = new InsensitiveIn(['Laravel', 'Framework', 'PHP']);

        $this->assertSame('insensitive_in:"Laravel","Framework","PHP"', (string) $rule);

        $rule = new InsensitiveIn(['Life, the Universe and Everything', 'this is a "quote"']);

        $this->assertSame('insensitive_in:"Life, the Universe and Everything","this is a ""quote"""', (string) $rule);

        $rule = new InsensitiveIn(["a,b\nc,d"]);

        $this->assertSame("insensitive_in:\"a,b\nc,d\"", (string) $rule);

        $rule = Rule::insensitiveIn([1, 2, 3, 4]);

        $this->assertSame('insensitive_in:"1","2","3","4"', (string) $rule);

        $rule = Rule::insensitiveIn(collect([1, 2, 3, 4]));

        $this->assertSame('insensitive_in:"1","2","3","4"', (string) $rule);

        $rule = Rule::InsensitiveIn(new Values);

        $this->assertSame('insensitive_in:"1","2","3","4"', (string) $rule);

        $rule = Rule::InsensitiveIn('1', '2', '3', '4');

        $this->assertSame('insensitive_in:"1","2","3","4"', (string) $rule);
    }
}
