<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationTypeRuleTest extends TestCase
{
    public function testTypeValidation()
    {
        $trans = new Translator(new ArrayLoader, 'en');

        $v = new Validator($trans, ['foo' => 'not an array'], ['foo' => Rule::type()->array()]);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['foo' => []], ['foo' => ['nullable', Rule::type()->array()]]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'not a bool'], ['foo' => Rule::type()->bool()]);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['foo' => true], ['foo' => Rule::type()->bool()]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'not a float'], ['foo' => Rule::type()->float()]);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['foo' => 1.1], ['foo' => Rule::type()->float()]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'not an int'], ['foo' => Rule::type()->int()]);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['foo' => 1], ['foo' => Rule::type()->int()]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'not numeric'], ['foo' => Rule::type()->numeric()]);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['foo' => 1], ['foo' => Rule::type()->numeric()]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => []], ['foo' => Rule::type()->scalar()]);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['foo' => 1], ['foo' => Rule::type()->scalar()]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => []], ['foo' => Rule::type()->string()]);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['foo' => 'string'], ['foo' => Rule::type()->string()]);
        $this->assertTrue($v->passes());
    }
}
