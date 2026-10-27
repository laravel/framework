<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationListRuleTest extends TestCase
{
    public function testItCorrectlyFormatsAStringVersionOfTheRule()
    {
        $this->assertSame('list', (string) Rule::list());
    }

    public function testItCorrectlyFormatsTheSizeConstraints()
    {
        $this->assertSame('list|min:1', (string) Rule::list()->min(1));
        $this->assertSame('list|max:5', (string) Rule::list()->max(5));
        $this->assertSame('list|between:1,5', (string) Rule::list()->between(1, 5));
        $this->assertSame('list|size:3', (string) Rule::list()->exactly(3));
        $this->assertSame('list|min:1|max:5', (string) Rule::list()->min(1)->max(5));
        $this->assertSame('list|min:1', (string) Rule::list()->min(1)->min(1));
    }

    public function testListValidation()
    {
        $trans = new Translator(new ArrayLoader, 'en');

        $v = new Validator($trans, ['foo' => ['bar', 'baz']], ['foo' => Rule::list()]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => ['key_1' => 'bar']], ['foo' => Rule::list()]);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['foo' => 'bar'], ['foo' => Rule::list()]);
        $this->assertTrue($v->fails());
    }

    public function testSizeValidation()
    {
        $trans = new Translator(new ArrayLoader, 'en');

        $v = new Validator($trans, ['foo' => []], ['foo' => Rule::list()->min(1)]);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['foo' => ['bar']], ['foo' => Rule::list()->min(1)]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => ['bar', 'baz']], ['foo' => Rule::list()->max(1)]);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['foo' => ['bar', 'baz']], ['foo' => Rule::list()->between(1, 2)]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => ['bar', 'baz', 'qux']], ['foo' => Rule::list()->between(1, 2)]);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['foo' => ['bar', 'baz']], ['foo' => Rule::list()->exactly(2)]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => ['bar']], ['foo' => Rule::list()->exactly(2)]);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['foo' => ['key_1' => 'bar', 'key_2' => 'baz']], ['foo' => Rule::list()->exactly(2)]);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['foo' => ['bar']], ['foo' => ['required', Rule::list()->max(1)]]);
        $this->assertTrue($v->passes());
    }

    public function testSizeValidationUsesTheArrayMessages()
    {
        $trans = new Translator(new ArrayLoader, 'en');
        $trans->addLines(['validation.size.array' => 'The :attribute must contain :size items.'], 'en');

        $v = new Validator($trans, ['foo' => ['bar']], ['foo' => Rule::list()->exactly(2)]);
        $this->assertTrue($v->fails());
        $this->assertSame('The foo must contain 2 items.', $v->messages()->first('foo'));
    }
}
