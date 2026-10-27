<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Tests\Validation\Fixtures\ArrayKeys;
use Illuminate\Tests\Validation\Fixtures\ArrayKeysBacked;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

include_once 'Fixtures/Enums.php';

class ValidationArrayRuleTest extends TestCase
{
    public function testItCorrectlyFormatsAStringVersionOfTheRule()
    {
        $rule = Rule::array();

        $this->assertSame('array', (string) $rule);

        $rule = Rule::array([]);
        $this->assertSame('array', (string) $rule);

        $rule = Rule::array('key_1', 'key_2', 'key_3');

        $this->assertSame('array:key_1,key_2,key_3', (string) $rule);

        $rule = Rule::array(['key_1', 'key_2', 'key_3']);

        $this->assertSame('array:key_1,key_2,key_3', (string) $rule);

        $rule = Rule::array(collect(['key_1', 'key_2', 'key_3']));

        $this->assertSame('array:key_1,key_2,key_3', (string) $rule);

        $rule = Rule::array([ArrayKeys::key_1, ArrayKeys::key_2, ArrayKeys::key_3]);

        $this->assertSame('array:key_1,key_2,key_3', (string) $rule);

        $rule = Rule::array([ArrayKeysBacked::key_1, ArrayKeysBacked::key_2, ArrayKeysBacked::key_3]);

        $this->assertSame('array:key_1,key_2,key_3', (string) $rule);

        $rule = Rule::array(['key_1', 'key_1']);
        $this->assertSame('array:key_1,key_1', (string) $rule);

        $rule = Rule::array([1, 2, 3]);
        $this->assertSame('array:1,2,3', (string) $rule);
    }

    public function testItCorrectlyFormatsTheSizeConstraints()
    {
        $this->assertSame('array|min:1', (string) Rule::array()->min(1));
        $this->assertSame('array|max:5', (string) Rule::array()->max(5));
        $this->assertSame('array|between:1,5', (string) Rule::array()->between(1, 5));
        $this->assertSame('array|size:3', (string) Rule::array()->exactly(3));
        $this->assertSame('array|min:1|max:5', (string) Rule::array()->min(1)->max(5));
        $this->assertSame('array|min:1', (string) Rule::array()->min(1)->min(1));
        $this->assertSame('array:key_1,key_2|min:1', (string) Rule::array(['key_1', 'key_2'])->min(1));
    }

    public function testSizeValidation()
    {
        $trans = new Translator(new ArrayLoader, 'en');

        $v = new Validator($trans, ['foo' => []], ['foo' => Rule::array()->min(1)]);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['foo' => ['bar']], ['foo' => Rule::array()->min(1)]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => ['bar', 'baz']], ['foo' => Rule::array()->max(1)]);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['foo' => ['bar', 'baz']], ['foo' => Rule::array()->between(1, 2)]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => ['bar', 'baz', 'qux']], ['foo' => Rule::array()->between(1, 2)]);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['foo' => ['bar', 'baz']], ['foo' => Rule::array()->exactly(2)]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => ['bar']], ['foo' => Rule::array()->exactly(2)]);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['foo' => ['key_1' => 'bar', 'key_2' => 'baz']], ['foo' => Rule::array(['key_1'])->min(1)]);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['foo' => ['key_1' => 'bar']], ['foo' => ['required', Rule::array(['key_1'])->max(1)]]);
        $this->assertTrue($v->passes());
    }

    public function testArrayValidation()
    {
        $trans = new Translator(new ArrayLoader, 'en');

        $v = new Validator($trans, ['foo' => 'not an array'], ['foo' => Rule::array()]);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['foo' => (object) ['key_1' => 'bar']], ['foo' => Rule::array()]);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['foo' => null], ['foo' => ['nullable', Rule::array()]]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => []], ['foo' => Rule::array()]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => ['key_1' => []]], ['foo' => Rule::array(['key_1'])]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => ['bar']], ['foo' => (string) Rule::array()]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => ['key_1' => 'bar', 'key_2' => '']], ['foo' => Rule::array(['key_1', 'key_2'])]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => ['key_1' => 'bar', 'key_2' => '']], ['foo' => ['required', Rule::array(['key_1', 'key_2'])]]);
        $this->assertTrue($v->passes());
    }
}
