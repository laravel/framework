<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class ValidationRawRuleTest extends TestCase
{
    public function testRawRule()
    {
        $data = [
            'foo' => 'bar',
        ];
        
        $rules = [
            'foo' => [
                Rule::raw('required'),
                Rule::raw('in', 'bar'),
            ],
        ];

        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, $data, $rules);

        $this->assertTrue($v->passes());
        $this->assertEquals($data, $v->validated());
    }

    public function testRawRulesSupportReservedKeywords()
    {
        $data = [
            'foo' => '|',
            'bar' => ';',
            'baz' => ',',
        ];
        
        $rules = [
            'foo' => Rule::raw('in', '|'),
            'bar' => Rule::raw('in', ';'),
            'baz' => Rule::raw('in', ','),
        ];

        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, $data, $rules);

        $this->assertTrue($v->passes());
        $this->assertEquals($data, $v->validated());
    }

    public function testRawRulesCanBeAppliedToArrays()
    {
        $data = [
            'items' => [
                ['|foo' => '...', ';bar' => '...', '.baz' => '...'],
                ['|foo' => '...', ';bar' => '...', '.baz' => '...'],
                ['|foo' => '...', ';bar' => '...', '.baz' => '...'],
            ]
        ];
        
        $rules = [
            'items' => ['array'],
            'items.*.name' => [
                'array',
                Rule::raw('required_array_keys', ['|foo', ';bar', '.baz']),
            ]
        ];

        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, $data, $rules);

        $this->assertTrue($v->passes());
        $this->assertEquals($data, $v->validated());
    }

    public function testRawRulesCanBeUsedWithReservedKeywordFields()
    {
        $data = [
            '|foo' => null,
            ';baz' => 'zal',
            ',zee' => 'fur',
        ];
        
        $rules = [
            '|foo' => Rule::raw('required_if', [';baz', 'zal']),
            ',zee' => Rule::raw('prohibited_if', [';baz', 'zal']),
        ];

        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, $data, $rules);

        $this->assertFalse($v->passes());
        $this->assertEquals([
            '|foo' => ['validation.required_if'],
            ',zee' => ['validation.prohibited_if'],
        ], $v->getMessageBag()->toArray());
    }

    public function testRawRegexRule()
    {
        $data = ['x' => 'asdasdf'];
        
        $rules = ['x' => Rule::raw('regex', '/^[a-z]+$/i')];
    
        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, $data, $rules);

        $this->assertTrue($v->passes());
    }

    protected function getTranslator()
    {
        return m::mock(TranslatorContract::class);
    }

    public function getIlluminateArrayTranslator()
    {
        return new Translator(
            new ArrayLoader, 'en'
        );
    }

}
