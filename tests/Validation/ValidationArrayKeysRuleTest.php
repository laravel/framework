<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

include_once 'Enums.php';

class ValidationArrayKeysRuleTest extends TestCase
{
    public function testItCorrectlyFormatsAStringVersionOfTheRule()
    {
        $rule = Rule::arrayKeys('key_1', 'key_2', 'key_3');

        $this->assertSame('array_keys:key_1,key_2,key_3', (string) $rule);

        $rule = Rule::arrayKeys(['key_1', 'key_2', 'key_3']);

        $this->assertSame('array_keys:key_1,key_2,key_3', (string) $rule);

        $rule = Rule::arrayKeys(collect(['key_1', 'key_2', 'key_3']));

        $this->assertSame('array_keys:key_1,key_2,key_3', (string) $rule);

        $rule = Rule::arrayKeys([ArrayKeys::key_1, ArrayKeys::key_2, ArrayKeys::key_3]);

        $this->assertSame('array_keys:key_1,key_2,key_3', (string) $rule);

        $rule = Rule::arrayKeys([ArrayKeysBacked::key_1, ArrayKeysBacked::key_2, ArrayKeysBacked::key_3]);

        $this->assertSame('array_keys:key_1,key_2,key_3', (string) $rule);

        $rule = Rule::arrayKeys([1, 2, 3]);

        $this->assertSame('array_keys:1,2,3', (string) $rule);
    }

    public function testArrayKeysValidation()
    {
        $trans = new Translator(new ArrayLoader, 'en');

        $v = new Validator($trans, ['foo' => ['key_1' => 'bar', 'key_3' => 'baz']], ['foo' => Rule::arrayKeys(['key_1', 'key_2'])]);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['foo' => ['bar', 'baz']], ['foo' => Rule::arrayKeys(['key_1'])]);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['foo' => 'not an array'], ['foo' => Rule::arrayKeys(['key_1'])]);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['foo' => (object) ['key_1' => 'bar']], ['foo' => Rule::arrayKeys(['key_1'])]);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['foo' => ['key_1' => 'bar', 'key_2' => '']], ['foo' => Rule::arrayKeys(['key_1', 'key_2'])]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => ['key_1' => 'bar']], ['foo' => Rule::arrayKeys(['key_1', 'key_2'])]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => ['key_1' => []]], ['foo' => Rule::arrayKeys(['key_1'])]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => []], ['foo' => Rule::arrayKeys(['key_1', 'key_2'])]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => ['bar', 'baz']], ['foo' => Rule::arrayKeys([0, 1])]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => ['key_1' => 'bar']], ['foo' => (string) Rule::arrayKeys(['key_1'])]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => null], ['foo' => ['nullable', Rule::arrayKeys(['key_1'])]]);
        $this->assertTrue($v->passes());
    }

    public function testArrayKeysValidationRequiresAtLeastOneKey()
    {
        $trans = new Translator(new ArrayLoader, 'en');

        $v = new Validator($trans, ['foo' => ['key_1' => 'bar']], ['foo' => 'array_keys']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('Validation rule array_keys requires at least 1 parameters.');

        $v->passes();
    }

    public function testArrayKeysValidationErrorMessage()
    {
        $trans = new Translator(new ArrayLoader, 'en');

        $trans->addLines([
            'validation.array_keys' => 'The :attribute field must only contain the following keys: :values.',
        ], 'en');

        $v = new Validator($trans, ['foo' => ['key_1' => 'bar', 'key_3' => 'baz']], ['foo' => Rule::arrayKeys(['key_1', 'key_2'])]);

        $this->assertTrue($v->fails());
        $this->assertSame(
            'The foo field must only contain the following keys: key_1, key_2.',
            $v->messages()->first('foo')
        );
        $this->assertSame(['ArrayKeys' => ['key_1', 'key_2']], $v->failed()['foo']);
    }

    public function testArrayKeysValidationErrorMessageCanReferenceTheUnexpectedKeys()
    {
        $trans = new Translator(new ArrayLoader, 'en');

        $v = new Validator(
            $trans,
            ['foo' => ['key_3' => 'bar', 'key_1' => 'baz', 'key_4' => 'qux']],
            ['foo' => Rule::arrayKeys(['key_1', 'key_2'])],
            ['foo.array_keys' => 'The :attribute field does not accept :unexpected. Accepted keys: :values.']
        );

        $this->assertTrue($v->fails());
        $this->assertSame(
            'The foo field does not accept key_3, key_4. Accepted keys: key_1, key_2.',
            $v->messages()->first('foo')
        );
    }

    public function testUnexpectedKeysArePlaceholderSafeAndEmptyForNonArrays()
    {
        $trans = new Translator(new ArrayLoader, 'en');

        $v = new Validator(
            $trans,
            ['foo' => ['key_1' => 'a', ':values' => 'b', ':attribute' => 'c']],
            ['foo' => Rule::arrayKeys(['key_1'])],
            ['foo.array_keys' => 'Unexpected keys: :unexpected. Accepted: :values.']
        );

        $this->assertTrue($v->fails());
        $this->assertSame('Unexpected keys: :values, :attribute. Accepted: key_1.', $v->messages()->first('foo'));

        $v = new Validator(
            $trans,
            ['foo' => 'not an array'],
            ['foo' => Rule::arrayKeys(['key_1'])],
            ['foo.array_keys' => 'Unexpected keys: :unexpected.']
        );

        $this->assertTrue($v->fails());
        $this->assertSame('Unexpected keys: .', $v->messages()->first('foo'));
    }
}
