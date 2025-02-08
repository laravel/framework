<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Validation\Rule;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class ValidationRegExpRuleTest extends TestCase
{
    public function testRegExpRuleStringification()
    {
        $rule = Rule::regex('/[a-z]/i');

        $this->assertSame('regex:/[a-z]/i', (string) $rule);
    }

    public function testNotRegExpRuleStringification()
    {
        $rule = Rule::regex('/[a-z]/i')->not();

        $this->assertSame('not_regex:/[a-z]/i', (string) $rule);
    }

    #[TestWith(['/[a-z]/', ['i'], 'regex:/[a-z]/i'])]
    #[TestWith(['/[a-z]/i', ['i'], 'regex:/[a-z]/i'])]
    #[TestWith(['/[a-z]/g', ['i'], 'regex:/[a-z]/gi'])]
    public function testRegExpRuleConstructorFlagsStringification(string $input, array $flags, string $output)
    {
        $rule = Rule::regex($input, $flags);

        $this->assertSame($output, (string) $rule);
    }

    public function tesRegExpRuleConstructorFlagDataTypesStringification()
    {
        $rule = Rule::regex('/[a-z]/', []);

        $this->assertSame('regex:/[a-z]/', (string) $rule);

        $rule = Rule::regex('/[a-z]/', ['i']);

        $this->assertSame('regex:/[a-z]/i', (string) $rule);

        $rule = Rule::regex('/[a-z]/', collect(['i']));

        $this->assertSame('regex:/[a-z]/i', (string) $rule);
    }

    public function testRegExpRuleFlagsStringification()
    {
        $rule = Rule::regex('/[a-z]/')->flags(null);

        $this->assertSame('regex:/[a-z]/', (string) $rule);

        $rule = Rule::regex('/[a-z]/')->flags([]);

        $this->assertSame('regex:/[a-z]/', (string) $rule);

        $rule = Rule::regex('/[a-z]/')->flags(['i']);

        $this->assertSame('regex:/[a-z]/i', (string) $rule);

        $rule = Rule::regex('/[a-z]/')->flags(collect(['i']));

        $this->assertSame('regex:/[a-z]/i', (string) $rule);
    }

    #[TestWith(['i', 'regex:/[a-z]/i'])]
    #[TestWith(['g', 'regex:/[a-z]/ig'])]
    public function testRegExpRuleFlagStringification(string $input, string $output)
    {
        $rule = Rule::regex('/[a-z]/i')->flag($input);

        $this->assertSame($output, (string) $rule);
    }
}
