<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\StringRule;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationStringRuleTest extends TestCase
{
    public function testDefaultStringRule()
    {
        $rule = Rule::string();
        $this->assertEquals('string', (string) $rule);

        $rule = new StringRule();
        $this->assertSame('string', (string) $rule);
    }

    public function testAlphaRule()
    {
        $rule = Rule::string()->alpha();
        $this->assertEquals('string|alpha', (string) $rule);

        $rule = Rule::string()->alpha(true);
        $this->assertEquals('string|alpha:ascii', (string) $rule);
    }

    public function testAlphaNumericRule()
    {
        $rule = Rule::string()->alphaNumeric();
        $this->assertEquals('string|alpha_num', (string) $rule);

        $rule = Rule::string()->alphaNumeric(true);
        $this->assertEquals('string|alpha_num:ascii', (string) $rule);
    }

    public function testAlphaDashRule()
    {
        $rule = Rule::string()->alphaDash();
        $this->assertEquals('string|alpha_dash', (string) $rule);

        $rule = Rule::string()->alphaDash(true);
        $this->assertEquals('string|alpha_dash:ascii', (string) $rule);
    }

    public function testAsciiRule()
    {
        $rule = Rule::string()->ascii();
        $this->assertEquals('string|ascii', (string) $rule);
    }

    public function testHexColorRule()
    {
        $rule = Rule::string()->hexColor();
        $this->assertEquals('string|hex_color', (string) $rule);
    }

    public function testIpAddressRule()
    {
        $rule = Rule::string()->ipAddress();
        $this->assertEquals('string|ip', (string) $rule);

        $rule = Rule::string()->ipAddress(4);
        $this->assertEquals('string|ipv4', (string) $rule);

        $rule = Rule::string()->ipAddress(6);
        $this->assertEquals('string|ipv6', (string) $rule);

        $rule = Rule::string()->ipv4();
        $this->assertEquals('string|ipv4', (string) $rule);

        $rule = Rule::string()->ipv6();
        $this->assertEquals('string|ipv6', (string) $rule);
    }

    public function testMacAddressRule()
    {
        $rule = Rule::string()->macAddress();
        $this->assertEquals('string|mac_address', (string) $rule);
    }

    public function testJsonRule()
    {
        $rule = Rule::string()->json();
        $this->assertEquals('string|json', (string) $rule);
    }

    public function testDoesntStartWithRule()
    {
        $rule = Rule::string()->doesntStartWith('foo');
        $this->assertEquals('string|doesnt_start_with:foo', (string) $rule);

        $rule = Rule::string()->doesntStartWith(['foo', 'bar']);
        $this->assertEquals('string|doesnt_start_with:foo,bar', (string) $rule);

        $rule = Rule::string()->doesntStartWith('foo', 'bar');
        $this->assertEquals('string|doesnt_start_with:foo,bar', (string) $rule);
    }

    public function testDoesntEndWithRule()
    {
        $rule = Rule::string()->doesntEndWith('foo');
        $this->assertEquals('string|doesnt_end_with:foo', (string) $rule);

        $rule = Rule::string()->doesntEndWith(['foo', 'bar']);
        $this->assertEquals('string|doesnt_end_with:foo,bar', (string) $rule);

        $rule = Rule::string()->doesntEndWith('foo', 'bar');
        $this->assertEquals('string|doesnt_end_with:foo,bar', (string) $rule);
    }

    public function testStartsWithRule()
    {
        $rule = Rule::string()->startsWith('foo');
        $this->assertEquals('string|starts_with:foo', (string) $rule);

        $rule = Rule::string()->startsWith(['foo', 'bar']);
        $this->assertEquals('string|starts_with:foo,bar', (string) $rule);

        $rule = Rule::string()->startsWith('foo', 'bar');
        $this->assertEquals('string|starts_with:foo,bar', (string) $rule);
    }

    public function testEndsWithRule()
    {
        $rule = Rule::string()->endsWith('foo');
        $this->assertEquals('string|ends_with:foo', (string) $rule);

        $rule = Rule::string()->endsWith(['foo', 'bar']);
        $this->assertEquals('string|ends_with:foo,bar', (string) $rule);

        $rule = Rule::string()->endsWith('foo', 'bar');
        $this->assertEquals('string|ends_with:foo,bar', (string) $rule);
    }

    public function testLowercaseRule()
    {
        $rule = Rule::string()->lowercase();
        $this->assertEquals('string|lowercase', (string) $rule);
    }

    public function testUppercaseRule()
    {
        $rule = Rule::string()->uppercase();
        $this->assertEquals('string|uppercase', (string) $rule);
    }

    public function testLengthRule()
    {
        $rule = Rule::string()->length(10);
        $this->assertEquals('string|size:10', (string) $rule);
    }

    public function testMaxLengthRule()
    {
        $rule = Rule::string()->maxLength(10);
        $this->assertEquals('string|max:10', (string) $rule);
    }

    public function testMinLengthRule()
    {
        $rule = Rule::string()->minLength(3);
        $this->assertEquals('string|min:3', (string) $rule);
    }

    public function testDifferentRule()
    {
        $rule = Rule::string()->different('foo');
        $this->assertEquals('string|different:foo', (string) $rule);
    }

    public function testSameRule()
    {
        $rule = Rule::string()->same('foo');
        $this->assertEquals('string|same:foo', (string) $rule);
    }

    public function testActiveUrlRule()
    {
        $rule = Rule::string()->activeUrl();
        $this->assertEquals('string|active_url', (string) $rule);
    }

    public function testUrlRule()
    {
        $rule = Rule::string()->url();
        $this->assertEquals('string|url', (string) $rule);

        $rule = Rule::string()->url('http');
        $this->assertEquals('string|url:http', (string) $rule);

        $rule = Rule::string()->url(['http', 'https']);
        $this->assertEquals('string|url:http,https', (string) $rule);

        $rule = Rule::string()->url('http', 'https');
        $this->assertEquals('string|url:http,https', (string) $rule);
    }

    public function testChainedRules()
    {
        $rule = Rule::string()
            ->minLength(3)
            ->maxLength(10)
            ->alpha(true)
            ->different('foo');

        $this->assertEquals('string|min:3|max:10|alpha:ascii|different:foo', (string) $rule);

        $rule = Rule::string()
            ->hexColor()
            ->when(true, function ($rule) {
                $rule->same('foo');
            })
            ->unless(true, function ($rule) {
                $rule->different('bar');
            });
        $this->assertSame('string|hex_color|same:foo', (string) $rule);
    }

    public function testStringRuleValidation()
    {
        $trans = new Translator(new ArrayLoader, 'en');

        $rule = Rule::string();

        $validator = new Validator(
            $trans,
            ['foo' => [1, 2, 3]],
            ['foo' => $rule]
        );

        $this->assertSame(
            $trans->get('validation.string'),
            $validator->errors()->first('foo')
        );

        $validator = new Validator(
            $trans,
            ['foo' => 'bar'],
            ['foo' => $rule]
        );

        $this->assertEmpty($validator->errors()->first('foo'));

        $rule = Rule::string()->alpha(true)->minLength(3)->maxLength(10);

        $validator = new Validator(
            $trans,
            ['foo' => 'bar'],
            ['foo' => (string) $rule]
        );

        $this->assertEmpty($validator->errors()->first('foo'));

        $rule = Rule::string()->different('bar');

        $validator = new Validator(
            $trans,
            ['foo' => 'new', 'bar' => 'old'],
            ['foo' => (string) $rule]
        );

        $this->assertEmpty($validator->errors()->first('foo'));

        $rule = Rule::string()->length(5);

        $validator = new Validator(
            $trans,
            ['foo' => 'field'],
            ['foo' => (string) $rule]
        );

        $this->assertEmpty($validator->errors()->first('foo'));

        $rule = Rule::string()->length(5)->ascii()->lowercase();

        $validator = new Validator(
            $trans,
            ['foo' => 'field'],
            ['foo' => [$rule]]
        );

        $this->assertEmpty($validator->errors()->first('foo'));
    }
}
