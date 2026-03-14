<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Url;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationUrlRuleTest extends TestCase
{
    public function testDefaultUrlRule()
    {
        $rule = Rule::url();
        $this->assertSame('url', (string) $rule);

        $rule = new Url();
        $this->assertSame('url', (string) $rule);
    }

    public function testSchemesRuleWithArray()
    {
        $rule = Rule::url()->schemes(['https']);
        $this->assertSame('url:https', (string) $rule);

        $rule = Rule::url()->schemes(['http', 'https']);
        $this->assertSame('url:http,https', (string) $rule);
    }

    public function testSchemesRuleWithVariadic()
    {
        $rule = Rule::url()->schemes('https');
        $this->assertSame('url:https', (string) $rule);

        $rule = Rule::url()->schemes('http', 'https');
        $this->assertSame('url:http,https', (string) $rule);
    }

    public function testSchemesReplacesOnSubsequentCalls()
    {
        $rule = Rule::url()->schemes(['http', 'https'])->schemes(['https']);
        $this->assertSame('url:https', (string) $rule);
    }

    public function testHttpsOnlyRule()
    {
        $rule = Rule::url()->httpsOnly();
        $this->assertSame('url:https', (string) $rule);
    }

    public function testActiveUrlRule()
    {
        $rule = Rule::url()->active();
        $this->assertSame('url|active_url', (string) $rule);
    }

    public function testChainedRules()
    {
        $rule = Rule::url()
            ->schemes(['https'])
            ->active();
        $this->assertSame('url:https|active_url', (string) $rule);
    }

    public function testUrlValidation()
    {
        $trans = new Translator(new ArrayLoader, 'en');

        $rule = Rule::url();

        $validator = new Validator(
            $trans,
            ['field' => 'not-a-url'],
            ['field' => $rule]
        );

        $this->assertSame(
            $trans->get('validation.url'),
            $validator->errors()->first('field')
        );

        $validator = new Validator(
            $trans,
            ['field' => 'https://example.com'],
            ['field' => $rule]
        );

        $this->assertEmpty($validator->errors()->first('field'));

        $rule = Rule::url()->schemes(['https']);

        $validator = new Validator(
            $trans,
            ['field' => 'https://example.com'],
            ['field' => $rule]
        );

        $this->assertEmpty($validator->errors()->first('field'));

        $rule = Rule::url()->schemes(['https']);

        $validator = new Validator(
            $trans,
            ['field' => 'http://example.com'],
            ['field' => $rule]
        );

        $this->assertSame(
            $trans->get('validation.url'),
            $validator->errors()->first('field')
        );
    }

    public function testConditionalRules()
    {
        $rule = Rule::url()
            ->when(true, function ($rule) {
                $rule->httpsOnly();
            });
        $this->assertSame('url:https', (string) $rule);
    }
}
