<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\ArrayRule;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

include_once 'Enums.php';

class ValidationArrayRuleTest extends TestCase
{
    public function testItCorrectlyFormatsAStringVersionOfTheRule()
    {
        $rule = Rule::array();

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
    }

    public function testDefaultArrayRule()
    {
        $rule = Rule::array();
        $this->assertEquals('array', (string) $rule);

        $rule = new ArrayRule();
        $this->assertSame('array', (string) $rule);
    }

    public function testDefaultArrayRuleWithKeys()
    {
        $rule = Rule::array(['a', 'b']);
        $this->assertEquals('array:a,b', (string) $rule);

        $rule = new ArrayRule([1, 2]);
        $this->assertSame('array:1,2', (string) $rule);
    }

    public function testDistinctValidation()
    {
        $rule = Rule::array()->distinct();
        $this->assertEquals('array|distinct', (string) $rule);

        $rule = Rule::array()->distinct(strict: true);
        $this->assertEquals('array|distinct:strict', (string) $rule);
    }

    public function testMaxRule()
    {
        $rule = Rule::array()->max(10);
        $this->assertEquals('array|max:10', (string) $rule);
    }

    public function testMinRule()
    {
        $rule = Rule::array()->min(10);
        $this->assertEquals('array|min:10', (string) $rule);
    }

    public function testSizeRule()
    {
        $rule = Rule::array()->size(10);
        $this->assertEquals('array|size:10', (string) $rule);
    }

    public function testBetweenRule()
    {
        $rule = Rule::array()->between(1, 5);
        $this->assertEquals('array|between:1,5', (string) $rule);
    }

    public function testListRule()
    {
        $rule = Rule::array()->list();
        $this->assertEquals('array|list', (string) $rule);
    }

    public function testContainsRule()
    {
        $rule = Rule::array()->contains(['a', 'b']);
        $this->assertEquals('array|contains:a,b', (string) $rule);

        $rule = Rule::array()->contains('a,b');
        $this->assertEquals('array|contains:a,b', (string) $rule);

        $rule = Rule::array()->contains(collect(['key_1', 'key_2', 'key_3']));
        $this->assertSame('array|contains:key_1,key_2,key_3', (string) $rule);

        $rule = Rule::array()->contains([ArrayKeys::key_1, ArrayKeys::key_2, ArrayKeys::key_3]);
        $this->assertSame('array|contains:key_1,key_2,key_3', (string) $rule);

        $rule = Rule::array()->contains([ArrayKeysBacked::key_1, ArrayKeysBacked::key_2, ArrayKeysBacked::key_3]);
        $this->assertSame('array|contains:key_1,key_2,key_3', (string) $rule);
    }

    public function testInArrayRule()
    {
        $rule = Rule::array()->inArray('another_filed');
        $this->assertEquals('array|in_array:another_filed', (string) $rule);
    }

    public function testChainedRules()
    {
        $rule = Rule::array()
            ->min(5)
            ->max(10)
            ->distinct()
            ->list();
        $this->assertEquals('array|min:5|max:10|distinct|list', (string) $rule);

        $rule = Rule::array()
            ->size(5)
            ->when(true, function ($rule) {
                $rule->distinct();
            })
            ->unless(false, function ($rule) {
                $rule->list();
            });
        $this->assertSame('array|size:5|distinct|list', (string) $rule);
    }

    public function testArrayValidation()
    {
        $trans = new Translator(new ArrayLoader, 'en');

        $v = new Validator($trans, ['foo' => 'not an array'], ['foo' => Rule::array()]);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['foo' => ['bar']], ['foo' => (string) Rule::array()]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => ['key_1' => 'bar', 'key_2' => '']], ['foo' => Rule::array(['key_1', 'key_2'])]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => ['key_1' => 'bar', 'key_2' => '']], ['foo' => ['required', Rule::array(['key_1', 'key_2'])]]);
        $this->assertTrue($v->passes());

        $v = new Validator(
            $trans,
            ['foo' => [1, 2, 3]],
            ['foo' => (string) Rule::array()->list()]
        );
        $this->assertTrue($v->passes());

        $v = new Validator(
            $trans,
            ['foo' => [1, 2, 3]],
            ['foo' => (string) Rule::array()->max(3)]
        );
        $this->assertTrue($v->passes());

        $v = new Validator(
            $trans,
            ['foo' => [1, 2, 3]],
            ['foo' => (string) Rule::array()->min(3)]
        );
        $this->assertTrue($v->passes());

        $v = new Validator(
            $trans,
            ['foo' => [1, 2, 3]],
            ['foo' => (string) Rule::array()->between(3, 5)]
        );
        $this->assertTrue($v->passes());

        $v = new Validator(
            $trans,
            ['foo' => [1, 2, 3]],
            ['foo' => (string) Rule::array()->size(3)]
        );
        $this->assertTrue($v->passes());

        $v = new Validator(
            $trans,
            ['foo' => [1, 2, 3]],
            ['foo' => (string) Rule::array()->distinct()]
        );
        $this->assertTrue($v->passes());

        $v = new Validator(
            $trans,
            ['foo' => [1, 2, 3]],
            ['foo' => (string) Rule::array()->contains(1)]
        );
        $this->assertTrue($v->passes());

        $v = new Validator(
            $trans,
            ['foo' => [1, 2, 3]],
            ['foo' => ['required', Rule::array()->list()]]
        );
        $this->assertTrue($v->passes());

        $v = new Validator(
            $trans,
            ['foo' => [1, 2, 3]],
            ['foo' => ['required', Rule::array()->max(3)]]
        );
        $this->assertTrue($v->passes());

        $v = new Validator(
            $trans,
            ['foo' => [1, 2, 3]],
            ['foo' => ['required', Rule::array()->min(3)]]
        );
        $this->assertTrue($v->passes());

        $v = new Validator(
            $trans,
            ['foo' => [1, 2, 3]],
            ['foo' => ['required', Rule::array()->between(3, 5)]]
        );
        $this->assertTrue($v->passes());

        $v = new Validator(
            $trans,
            ['foo' => [1, 2, 3]],
            ['foo' => ['required', Rule::array()->size(3)]]
        );
        $this->assertTrue($v->passes());

        $v = new Validator(
            $trans,
            ['foo' => [1, 2, 3]],
            ['foo' => ['required', Rule::array()->distinct()]]
        );
        $this->assertTrue($v->passes());

        $v = new Validator(
            $trans,
            ['foo' => [1, 2, 3]],
            ['foo' => ['required', Rule::array()->contains(1)]]
        );
        $this->assertTrue($v->passes());
    }
}
