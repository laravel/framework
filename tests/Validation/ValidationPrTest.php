<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationRuleParser;
use Illuminate\Validation\Validator;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class ValidationPrTest extends TestCase
{
    public function testWithComma()
    {
        $data = [
            'foo' => ',bar',
        ];
        
        $rules = [
            'foo' => ['required', Rule::make('in', ',bar')],
        ];

        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, $data, $rules);

        $this->assertTrue($v->passes());
    }

    public function testOne()
    {
        $data = [
            'repeater' => [
                [
                    'status' => [
                        '|code' => 123, 
                        '|terminology' => 'openehr'
                    ]
                ]
            ]
        ];
        
        $rules = [
            'repeater' => ['array'],
            'repeater.*.status' => [
                'array',
                Rule::make('required_array_keys', ['|code', '|terminology']),
            ]
        ];

        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, $data, $rules);

        $this->assertTrue($v->passes());
    }

    public function testTwo()
    {
        $data = [
            '|code' => '1234',
            '|terminology' => 'openehr',
        ];
        
        $rules = [
            '|code' => Rule::make('required_if', ['|terminology', 'openehr']),
        ];

        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, $data, $rules);

        $this->assertTrue($v->passes());
    }

    public function testThree()
    {
        $data = [
            '|terminology' => 'foo'
        ];
        
        $rules = [
            '|code' => Rule::make('required_unless', ['|terminology','openehr']),
        ];

        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, $data, $rules);

        $this->assertFalse($v->passes());
    }

    public function testFour()
    {
        $data = [
            '|code' => true,
            '|terminology' => 'openehr'
        ];
        
        $rules = [
            '|code' => Rule::make('accepted_if', ['|terminology','openehr']),
        ];

        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, $data, $rules);

        $this->assertTrue($v->passes());
    }

    public function testFive()
    {
        $data = [
            'myfield' => 'test',
        ];
        
        $rules = [
            'myfield' => Rule::make('ends_with', ['|foo','bar']),
        ];

        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, $data, $rules);

        $this->assertFalse($v->passes());
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