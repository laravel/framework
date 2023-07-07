<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Tests\Validation\fixtures\StatusEnum;
use Illuminate\Tests\Validation\fixtures\Values;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\ArrayRule;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ValidationArrayRuleTest extends TestCase
{
    public function testItCorrectlyFormatsAStringVersionOfTheRule()
    {
        $rule = new ArrayRule(['Laravel', 'Framework', 'PHP']);

        $this->assertSame('array:"Laravel","Framework","PHP"', (string) $rule);

        $rule = new ArrayRule([1, 2, 3, 4]);

        $this->assertSame('array:"1","2","3","4"', (string) $rule);

        $rule = new ArrayRule();

        $this->assertSame('array', (string) $rule);

        $rule = new ArrayRule(StatusEnum::class);

        $this->assertSame('array:"draft","published","archived"', (string) $rule);

        $rule = Rule::isArray([1, 2, 3, 4]);

        $this->assertSame('array:"1","2","3","4"', (string) $rule);

        $rule = Rule::isArray('1', '2', '3', '4');

        $this->assertSame('array:"1","2","3","4"', (string) $rule);

        $rule = Rule::isArray(1, 2, 3, 4);

        $this->assertSame('array:"1","2","3","4"', (string) $rule);

        $rule = Rule::isArray(collect([1, 2, 3, 4]));

        $this->assertSame('array:"1","2","3","4"', (string) $rule);

        $rule = Rule::isArray(new Values);

        $this->assertSame('array:"1","2","3","4"', (string) $rule);

        $rule = Rule::isArray('1', '2', '3', '4');

        $this->assertSame('array:"1","2","3","4"', (string) $rule);

        $rule = Rule::isArray(StatusEnum::class);

        $this->assertSame('array:"draft","published","archived"', (string) $rule);

        $rule = Rule::isArray();

        $this->assertSame('array', (string) $rule);
    }

    public function testItWillThrowAnExceptionIfAnInvalidEnumIsGiven()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The provided condition must be an enum.');

        new ArrayRule('Illuminate\Tests\Validation\FakeEnum');
    }
}
