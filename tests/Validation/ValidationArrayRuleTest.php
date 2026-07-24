<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

include_once 'Enums.php';

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

    public function testArrayValidation()
    {
        $trans = new Translator(new ArrayLoader, 'en');

        $v = new Validator($trans, ['foo' => 'not an array'], ['foo' => Rule::array()]);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['foo' => (object) ['key_1' => 'bar']], ['foo' => Rule::array()]);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['foo' => ['key_1' => 'bar', 'key_3' => 'baz']], ['foo' => Rule::array(['key_1', 'key_2'])]);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['foo' => ['bar', 'baz']], ['foo' => Rule::array(['key_1'])]);
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

        $v = new Validator($trans, ['foo' => ['key_1' => 'bar']], ['foo' => Rule::array(['key_1', 'key_2'])]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => []], ['foo' => Rule::array(['key_1', 'key_2'])]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => ['bar', 'baz']], ['foo' => Rule::array([0, 1])]);
        $this->assertTrue($v->passes());
    }

    public function testArrayValidationErrorMessage()
    {
        $trans = $this->getTranslator();

        $v = new Validator($trans, ['foo' => ['key_3' => 'bar', 'key_1' => 'baz', 'key_4' => 'qux']], ['foo' => Rule::array(['key_1', 'key_2'])]);

        $this->assertTrue($v->fails());
        $this->assertSame(
            'The foo field must only contain the following keys: key_1, key_2. Unexpected: key_3, key_4.',
            $v->messages()->first('foo')
        );
    }

    public function testArrayValidationErrorMessageFallsBackWhenNotConstrainedByKeys()
    {
        $trans = $this->getTranslator();

        $v = new Validator($trans, ['foo' => 'not an array'], ['foo' => Rule::array(['key_1', 'key_2'])]);
        $this->assertTrue($v->fails());
        $this->assertSame('The foo field must be an array.', $v->messages()->first('foo'));

        $v = new Validator($trans, ['foo' => 'not an array'], ['foo' => Rule::array()]);
        $this->assertTrue($v->fails());
        $this->assertSame('The foo field must be an array.', $v->messages()->first('foo'));
    }

    public function testArrayValidationFailureIsRecordedAsTheArrayRule()
    {
        $trans = $this->getTranslator();

        $v = new Validator($trans, ['foo' => ['key_1' => 'bar', 'key_3' => 'baz']], ['foo' => Rule::array(['key_1', 'key_2'])]);

        $this->assertTrue($v->fails());
        $this->assertSame(['Array' => ['key_1', 'key_2']], $v->failed()['foo']);
    }

    public function testArrayValidationErrorMessageCanBeCustomised()
    {
        $trans = $this->getTranslator();

        // A custom message may reference just the accepted keys...
        $v = new Validator(
            $trans,
            ['foo' => ['key_1' => 'bar', 'key_3' => 'baz']],
            ['foo' => Rule::array(['key_1', 'key_2'])],
            ['foo.array_keys' => 'The :attribute field only accepts :values.']
        );

        $this->assertTrue($v->fails());
        $this->assertSame('The foo field only accepts key_1, key_2.', $v->messages()->first('foo'));

        // ...or just the unexpected keys.
        $v = new Validator(
            $trans,
            ['foo' => ['key_1' => 'bar', 'key_3' => 'baz']],
            ['foo' => Rule::array(['key_1', 'key_2'])],
            ['foo.array_keys' => 'The :attribute field may not contain :unexpected.']
        );

        $this->assertTrue($v->fails());
        $this->assertSame('The foo field may not contain key_3.', $v->messages()->first('foo'));
    }

    public function testArrayValidationErrorMessageDoesNotReExpandUnexpectedKeys()
    {
        $trans = $this->getTranslator();

        $v = new Validator($trans, ['foo' => ['key_1' => 'a', ':values' => 'x', ':attribute' => 'y']], ['foo' => Rule::array(['key_1'])]);

        $this->assertTrue($v->fails());
        $this->assertSame(
            'The foo field must only contain the following keys: key_1. Unexpected: :values, :attribute.',
            $v->messages()->first('foo')
        );
    }

    protected function getTranslator()
    {
        $trans = new Translator(new ArrayLoader, 'en');

        $trans->addLines([
            'validation.array' => 'The :attribute field must be an array.',
            'validation.array_keys' => 'The :attribute field must only contain the following keys: :values. Unexpected: :unexpected.',
        ], 'en');

        return $trans;
    }
}
